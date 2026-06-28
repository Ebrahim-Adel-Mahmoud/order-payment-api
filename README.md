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

Public auth routes: `POST /api/auth/register`, `POST /api/auth/login`.

Protected routes require `Authorization: Bearer <token>`.

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

## API Requests & Responses

JSON bodies use snake_case. Paginated list responses include `data`, `links`, and `meta`.

### Auth

**Register** — `POST /api/auth/register`

Request:

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Response `201`:

```json
{
  "message": "User registered successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

**Login** — `POST /api/auth/login`

Request:

```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

Response `200`:

```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

**Login error** — `401`:

```json
{
  "message": "Invalid email or password."
}
```

**Logout** — `POST /api/auth/logout` → `200`:

```json
{
  "message": "Successfully logged out."
}
```

### Products

**List products** — `GET /api/products` (public)

Response `200`:

```json
{
  "data": [
    {
      "id": 1,
      "product_name": "Widget",
      "quantity": 100,
      "price": "10.00"
    },
    {
      "id": 2,
      "product_name": "Gadget",
      "quantity": 75,
      "price": "5.50"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 5 }
}
```

### Orders

**Create order** — `POST /api/orders`

`customer_name` and `customer_email` are taken from the authenticated user. Item price comes from the product catalog.

Request:

```json
{
  "items": [
    { "product_id": 1, "quantity": 2 }
  ]
}
```

Response `201`:

```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "status": "pending",
    "total": "20.00",
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "product_name": "Widget",
        "quantity": 2,
        "price": "10.00",
        "line_total": "20.00"
      }
    ],
    "payments": [],
    "created_at": "2026-06-26T12:00:00+00:00",
    "updated_at": "2026-06-26T12:00:00+00:00"
  }
}
```

**Validation error** — `422`:

```json
{
  "message": "The product field is required.",
  "errors": {
    "items.0.product_id": ["The product field is required."]
  }
}
```

**Update order** — `PATCH /api/orders/{id}`

Request:

```json
{
  "status": "confirmed",
  "items": [
    { "product_id": 2, "quantity": 1 }
  ]
}
```

Response `200`: same shape as create order response with updated fields.

**Delete order** — `DELETE /api/orders/{id}` → `204` (empty body)

**Delete blocked (has payments)** — `422`:

```json
{
  "message": "Orders with associated payments cannot be deleted."
}
```

**List orders** — `GET /api/orders?status=confirmed`

Response `200`:

```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "customer_name": "John Doe",
      "customer_email": "john@example.com",
      "status": "confirmed",
      "total": "20.00",
      "items": [
        {
          "id": 1,
          "product_id": 1,
          "product_name": "Widget",
          "quantity": 2,
          "price": "10.00",
          "line_total": "20.00"
        }
      ],
      "payments": [],
      "created_at": "2026-06-26T12:00:00+00:00",
      "updated_at": "2026-06-26T12:02:00+00:00"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 }
}
```

**Get order** — `GET /api/orders/{id}` → `200`: single order object (same shape as one item in the list `data` array above).

**Unauthorized** — missing or invalid token → `401`:

```json
{
  "message": "Unauthenticated."
}
```

### Payments

**Process payment** — `POST /api/orders/{id}/payments`

Request:

```json
{
  "method": "credit_card",
  "card_last_four": "4242"
}
```

Response `201`:

```json
{
  "data": {
    "id": 1,
    "order_id": 1,
    "status": "successful",
    "method": "credit_card",
    "amount": "20.00",
    "transaction_reference": "cc_ABC123XYZ",
    "gateway_response": {
      "gateway": "credit_card",
      "message": "Simulated credit card charge approved."
    },
    "transactions": [
      {
        "id": 1,
        "gateway": "credit_card",
        "type": "charge",
        "status": "successful",
        "amount": "20.00",
        "reference": "cc_ABC123XYZ",
        "request_payload": { "card_last_four": "4242" },
        "response_payload": {
          "gateway": "credit_card",
          "message": "Simulated credit card charge approved."
        },
        "created_at": "2026-06-26T12:05:00+00:00"
      }
    ],
    "created_at": "2026-06-26T12:05:00+00:00",
    "updated_at": "2026-06-26T12:05:00+00:00"
  }
}
```

**Payment on non-confirmed order** — `422`:

```json
{
  "message": "Payments can only be processed for confirmed orders."
}
```

**List payments** — `GET /api/payments` or `GET /api/orders/{id}/payments`

Response `200`:

```json
{
  "data": [
    {
      "id": 1,
      "order_id": 1,
      "status": "successful",
      "method": "credit_card",
      "amount": "20.00",
      "transaction_reference": "cc_ABC123XYZ",
      "gateway_response": {
        "gateway": "credit_card",
        "message": "Simulated credit card charge approved."
      },
      "transactions": [],
      "created_at": "2026-06-26T12:05:00+00:00",
      "updated_at": "2026-06-26T12:05:00+00:00"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 }
}
```

**Get payment** — `GET /api/payments/{id}` → `200`: single payment object (same shape as one item in the list `data` array above, with `transactions` loaded).

### Seeded products

Pick `product_id` from `GET /api/products` after running `php artisan db:seed`:

| product_name | stock quantity | price |
|--------------|----------------|-------|
| Widget | 100 | 10.00 |
| Gadget | 75 | 5.50 |
| Wireless Mouse | 50 | 29.99 |
| USB-C Hub | 40 | 45.00 |
| Mechanical Keyboard | 25 | 89.99 |

### Request format (quick reference)

**Create order:**

```json
{
  "items": [
    { "product_id": 1, "quantity": 2 }
  ]
}
```

**Update order:**

```json
{
  "status": "confirmed",
  "items": [
    { "product_id": 2, "quantity": 1 }
  ]
}
```

**Process payment:**

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
- Import into Postman, set `baseUrl`, then run: **Login** or **Register** → **List Products** → **Create Order** → **Confirm Order** → **Process Payment**
- Collection variables auto-set: `token`, `productId`, `orderId`, `paymentId`

## Assumptions

- JWT is used instead of Sanctum/Passport (required by the task spec).
- Payment gateways are simulated (no real external API calls).
- JSON snake_case is used for request/response fields.
- MySQL is used for development and testing.
