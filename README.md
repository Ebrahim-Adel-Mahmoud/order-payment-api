# Order & Payment Management API

Laravel 12 REST API for managing orders and payments, built with **JWT authentication** and an extensible **Strategy Pattern** for payment gateways.

## Features

- JWT authentication (register, login, logout)
- Product catalog seeded via `ProductSeeder` (read-only API listing)
- Payment processing via pluggable gateways (`credit_card`, `paypal`)
- Business rules enforced in services
- PHPUnit unit & feature tests

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.x

## Setup

```bash
git clone <repository-url> order-payment-api
cd order-payment-api
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Create databases:

```sql
CREATE DATABASE order_payment_api;
CREATE DATABASE order_payment_api_testing;
```

Run migrations and seed products:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

- API entrypoint: `http://127.0.0.1:8000/api`

## Authentication

All protected routes require `Authorization: Bearer <token>`.

```bash
# Register
POST /api/auth/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}

# Login
POST /api/auth/login
{
  "email": "john@example.com",
  "password": "password123"
}
```

Use the returned `access_token` in subsequent requests.

## API Overview

| Group | Endpoint | Description |
|-------|----------|-------------|
| Auth | `POST /api/auth/register` | Register user |
| Auth | `POST /api/auth/login` | Login & receive JWT |
| Auth | `POST /api/auth/logout` | Logout |
| Products | `GET /api/products` | List active seeded products (public) |
| Orders | `GET /api/orders` | List orders (paginated, filter `?status=`) |
| Orders | `POST /api/orders` | Create order |
| Orders | `GET /api/orders/{id}` | Get order |
| Orders | `PUT/PATCH /api/orders/{id}` | Update order |
| Orders | `DELETE /api/orders/{id}` | Delete order (blocked if payments exist) |
| Payments | `POST /api/orders/{id}/payments` | Process payment |
| Payments | `GET /api/orders/{id}/payments` | Payments for order |
| Payments | `GET /api/payments` | All payments |
| Payments | `GET /api/payments/{id}` | Payment details |

### Request format

JSON bodies use snake_case.

**Create order example** (`customer_name` / `customer_email` come from the authenticated user; price comes from the product catalog):

```json
{
  "items": [
    { "product_id": 1, "quantity": 2 }
  ]
}
```

**Update order example:**

```json
{
  "status": "confirmed",
  "items": [
    { "product_id": 2, "quantity": 1 }
  ]
}
```

**Seeded products** (`ProductSeeder`) — pick `product_id` from `GET /api/products`:

| product_name | stock quantity | price |
|--------------|----------------|-------|
| Widget | 100 | 10.00 |
| Gadget | 75 | 5.50 |
| Wireless Mouse | 50 | 29.99 |
| USB-C Hub | 40 | 45.00 |
| Mechanical Keyboard | 25 | 89.99 |

**Process payment example:**

```json
{
  "method": "credit_card",
  "card_last_four": "4242"
}
```

## Business Rules

1. Products catalog is seeded and listed via public `GET /api/products`.
2. Create order sends `product_id` + `quantity` only; price is read from the product catalog.
3. `customer_name` and `customer_email` on the order are taken from the authenticated user.
4. Each order item links to a product via `product_id` (FK on `order_items`).
5. Payments can only be processed for orders with `status = confirmed`.
6. Orders with associated payments cannot be deleted.
7. Order totals and line totals are calculated server-side.

### Data model relationships

```
User 1──* Order 1──* OrderItem *──1 Product
Order 1──* Payment
Payment 1──* PaymentTransaction
```

## Payment Gateway Extensibility

The payment system follows **SOLID** principles and the **Strategy Pattern**. Each charge is persisted as a **payment transaction** so gateway activity is auditable without changing core orchestration code.

### Architecture

```
PaymentService (DB transaction)
  ├── PaymentRepository          → payments record
  ├── PaymentTransactionRepository → gateway attempt log
  └── PaymentGatewayManager (Factory/Registry)
        └── PaymentGatewayInterface (Strategy)
              ├── CreditCardGateway (Adapter)
              └── PaypalGateway (Adapter)
```

**Flow:** create payment (pending) → log transaction (pending) → resolve gateway strategy → charge → update transaction + payment from `PaymentResult`.

### Adding a new gateway (3 steps)

**1. Create gateway class** implementing `PaymentGatewayInterface`:

```php
// app/Services/Payment/Gateways/StripeGateway.php
final class StripeGateway implements PaymentGatewayInterface
{
    public function getName(): string { return 'stripe'; }

    public function charge(PaymentContext $context): PaymentResult
    {
        // Adapt Stripe SDK response to PaymentResult DTO
    }
}
```

**2. Register in config** (`config/payment.php`):

```php
'gateways' => [
    'credit_card' => CreditCardGateway::class,
    'paypal' => PaypalGateway::class,
    'stripe' => StripeGateway::class,
],

'stripe' => [
    'secret_key' => env('STRIPE_SECRET_KEY'),
],
```

**3. Add environment variables** (`.env`):

```
STRIPE_SECRET_KEY=sk_test_...
```

No changes are required in controllers or `PaymentService` (**Open/Closed Principle**).

Optional refund support: implement the separate `Refundable` interface only for gateways that support refunds (**Interface Segregation Principle**).

## SOLID & Design Patterns

| Principle / Pattern | Implementation |
|---------------------|----------------|
| **SRP** | Form Requests (validation), Controllers (HTTP), Repositories (data access), Services (business rules), Gateways (provider logic) |
| **OCP** | New gateways via config + new class only |
| **LSP** | All gateways interchangeable via `PaymentGatewayInterface` |
| **ISP** | Small `PaymentGatewayInterface`; optional `Refundable` |
| **DIP** | Dependencies injected via `PaymentServiceProvider` |
| **Strategy** | Payment gateways behind common interface |
| **Factory/Registry** | `PaymentGatewayManager` |
| **Adapter** | Gateways map external responses to `PaymentResult` |
| **DTO** | `PaymentContext`, `PaymentResult` |
| **Repository** | `UserRepository`, `ProductRepository`, `OrderRepository`, `PaymentRepository`, `PaymentTransactionRepository` |
| **Service Layer** | `AuthService`, `ProductService`, `OrderService`, `PaymentService` |

## Testing

```bash
php artisan test
```

Tests use MySQL database `order_payment_api_testing` (configured in `phpunit.xml`).

## Documentation

- Postman collection: `docs/order-payment-api.postman_collection.json`
- Import into Postman and set collection variable `baseUrl` + `token`

## Assumptions

- JWT is used instead of Sanctum/Passport (required by the task spec).
- Payment gateways are simulated (no real external API calls).
- JSON snake_case is used for request/response fields.
- MySQL is used for development and testing.
