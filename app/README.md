# TechnoPay Wallet Payment Challenge

This project implements a **secure, concurrent-safe wallet-based invoice payment flow** using **Laravel 11**, with explicit confirmation (two-step verification), a global daily spending limit, and full transactional safety.

The focus is on **correctness under concurrency**, **clear boundaries**, and **extensibility** for future integrations (two-step verification, notifications, external payment providers, etc).

---

## 🧱 Tech Stack

- PHP 8.2+
- Laravel 11
- MySQL (or any relational DB supported by Laravel)
- No external packages
- PHPUnit for unit & feature tests

---

## 🏗 Project Structure

```txt
app
 ├── Domain
 │   ├── Invoice
 │   │   └── Models
 │   │       └── Invoice.php
 │   ├── Wallet
 │   │   └── Models
 │   │       ├── Wallet.php
 │   │       └── WalletTransaction.php
 │   └── Spending
 │       └── Models
 │           └── DailySpendingStat.php
 │
 ├── Services
 │   ├── Payments
 │   │   ├── InvoicePaymentService.php
 │   │   └── InvoicePaymentResult.php
 │   ├── Verification
 │   │   ├── ConfirmationServiceInterface.php
 │   │   └── MockConfirmationService.php
 │   └── Notifications
 │       ├── NotificationServiceInterface.php
 │       └── MockNotificationService.php
 │
 ├── Http
 │   ├── Controllers
 │   │   └── InvoicePaymentController.php
 │   └── Requests
 │       └── PayInvoiceRequest.php
 │
 └── Providers
     └── AppServiceProvider.php   # service bindings
```

Additional files:

```txt
config/payments.php              # global daily limit config
routes/api.php                   # payment endpoint
database/migrations              # invoices, wallets, transactions, daily stats
database/factories               # Invoice, Wallet, DailySpendingStat factories
tests/Unit                       # InvoicePaymentService tests
tests/Feature                    # API endpoint tests
```

---

## 🧩 Design & Patterns

The system uses a simple and clean layered architecture:

### Layers

- **Domain layer**
  - Eloquent models representing system entities.

- **Application / Service layer**
  - `InvoicePaymentService` encapsulates all business logic.
  - `InvoicePaymentResult` is used to return structured result data.

- **HTTP / Interface layer**
  - Handles requests, validation, responses.

### Key Design Patterns

- **Service Layer**
- **Dependency Inversion (SOLID)**
  - Interfaces: `ConfirmationServiceInterface`, `NotificationServiceInterface`
  - Implementations: mocks (can be replaced later)

- **Transaction Script**
  - Complete payment logic inside a single database transaction.
  - Includes row-level locking via `lockForUpdate()`.

- **DTO Pattern**
  - Clean data transfer using `InvoicePaymentResult`.

---

## 🔐 Business Rules

### ✔ Invoice Payment Rules
- Must be `pending`
- Must belong to the current user
- Must not be expired
- Must not be already paid

### ✔ Wallet Rules
- Must exist
- Must be `active`
- Must have *sufficient balance*

### ✔ User Rules
- Must not be blocked (`users.is_blocked = false`)

### ✔ Two-Step Verification
- Implemented using an interface + mock service
- Valid confirmation code (mock): `123456`

### ✔ Daily Spending Limit
- Global per-day spending limit stored in `daily_spending_stats`
- Row for today's date is locked to ensure concurrency safety
- Rejects payment if:
  ```
  total_spent + invoice.amount > daily_limit
  ```

### ✔ Accurate Timestamps
- `paid_at` recorded upon successful payment

### ✔ Refund on Failure
- The entire payment runs inside `DB::transaction()`
- Any failure → full rollback (automatic refund)

---

## 🔀 Concurrency & Safety

- `DB::transaction()` ensures atomic operations
- `lockForUpdate()` on:
  - Invoice row
  - Wallet row
  - Today's daily stats row

Guarantees:
- No double spending
- No two payments on the same invoice
- Daily spending limit integrity

---

## 🌐 API Endpoint

### POST `/api/invoices/{invoice}/pay`

#### Request Body:

```json
{
  "confirmation_code": "123456"
}
```

#### Success Response (200):

```json
{
  "success": true,
  "data": {
    "invoice": {
      "id": 1,
      "status": "paid",
      "amount": "1000.00",
      "paid_at": "2025-01-01T10:00:00Z"
    },
    "wallet": {
      "balance": "4000.00"
    }
  }
}
```

#### Failure Response (422):

```json
{
  "success": false,
  "error": "Invalid confirmation code."
}
```

Possible errors:
- Invoice expired
- Insufficient balance
- Wallet inactive
- User blocked
- Daily limit reached

---

## 🧪 Testing

Run all tests:

```bash
php artisan test
```

### Unit Tests
Located at:
```
tests/Unit/InvoicePaymentServiceTest.php
```

Covers:
- Successful payment
- Invalid confirmation code
- Expired invoice
- Insufficient balance
- Daily limit exceeded
- Re-paying an already paid invoice

### Feature Tests
Located at:
```
tests/Feature/PayInvoiceTest.php
```

---

## ⚙️ Setup Instructions

```bash
git clone <repo-url>
cd <project-folder>

cp .env.example .env
composer install
php artisan key:generate

# Update DB credentials in .env

php artisan migrate
php artisan test
php artisan serve
```

App available at:
```
http://localhost:8000
```

---

## 🐳 Optional: Docker

If Docker support is included:

### Start services:

```bash
docker compose up -d
```

### Run migrations:

```bash
docker compose exec app php artisan migrate
```

### Run tests:

```bash
docker compose exec app php artisan test
```

---
