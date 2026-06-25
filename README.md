# Order & Payment Management API

Laravel 12 REST API for managing orders and payments, built with **API Platform**, **JWT authentication**, and an extensible **Strategy Pattern** for payment gateways.

## Features

- JWT authentication (register, login, logout, refresh, me)
- Order CRUD with server-side total calculation
- Payment processing via pluggable gateways (`credit_card`, `paypal`)
- Business rules enforced in state processors/services
- Auto-generated OpenAPI docs (Swagger UI)
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

Run migrations:

```bash
php artisan migrate
php artisan serve
```

- API entrypoint: `http://127.0.0.1:8000/api`
- Swagger UI: `http://127.0.0.1:8000/api/docs`
- OpenAPI JSON: `http://127.0.0.1:8000/api/docs.json`

## Authentication

All API Platform resources require `Authorization: Bearer <token>`.

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
| Auth | `GET /api/auth/me` | Current user |
| Orders | `GET /api/orders` | List orders (paginated, filter `?status=`) |
| Orders | `POST /api/orders` | Create order |
| Orders | `GET /api/orders/{id}` | Get order |
| Orders | `PATCH /api/orders/{id}` | Update order |
| Orders | `DELETE /api/orders/{id}` | Delete order (blocked if payments exist) |
| Payments | `POST /api/orders/{id}/payments` | Process payment |
| Payments | `GET /api/orders/{id}/payments` | Payments for order |
| Payments | `GET /api/payments` | All payments |
| Payments | `GET /api/payments/{id}` | Payment details |

### Request format

API Platform uses camelCase in JSON bodies when `SnakeCaseToCamelCaseNameConverter` is enabled.

**Create order example:**

```json
{
  "customerName": "John Doe",
  "customerEmail": "john@example.com",
  "items": [
    { "productName": "Widget", "quantity": 2, "unitPrice": "15.50" }
  ]
}
```

**Process payment example:**

```json
{
  "method": "credit_card",
  "cardLastFour": "4242"
}
```

## Business Rules

1. Payments can only be processed for orders with `status = confirmed`.
2. Orders with associated payments cannot be deleted.
3. Order totals and line totals are calculated server-side.

## Payment Gateway Extensibility

The payment system follows **SOLID** principles and the **Strategy Pattern**.

### Architecture

```
PaymentService
  └── PaymentGatewayManager (Factory/Registry)
        └── PaymentGatewayInterface
              ├── CreditCardGateway (Adapter)
              └── PaypalGateway (Adapter)
```

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

No changes are required in controllers, processors, or `PaymentService` (**Open/Closed Principle**).

Optional refund support: implement the separate `Refundable` interface only for gateways that support refunds (**Interface Segregation Principle**).

## SOLID & Design Patterns

| Principle / Pattern | Implementation |
|---------------------|----------------|
| **SRP** | Form Requests (validation), Processors (orchestration), Services (business rules), Gateways (provider logic) |
| **OCP** | New gateways via config + new class only |
| **LSP** | All gateways interchangeable via `PaymentGatewayInterface` |
| **ISP** | Small `PaymentGatewayInterface`; optional `Refundable` |
| **DIP** | Dependencies injected via `PaymentServiceProvider` |
| **Strategy** | Payment gateways behind common interface |
| **Factory/Registry** | `PaymentGatewayManager` |
| **Adapter** | Gateways map external responses to `PaymentResult` |
| **DTO** | `PaymentContext`, `PaymentResult` |
| **Service Layer** | `PaymentService` |

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
- JSON camelCase is the primary request/response format; JSON-LD is also supported.
- MySQL is used for development and testing.
