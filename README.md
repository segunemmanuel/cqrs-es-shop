
# Laravel CQRS + Event Sourcing Shop

A demo **e-commerce backend** built with Laravel 11, demonstrating **CQRS** (Command Query Responsibility Segregation) and **Event Sourcing** patterns.

- Commands append **events** to an `event_store`.
- Projections build **read models** (`product_reads`, `inventory_reads`, `order_reads`) for fast queries.
- Tools to **rebuild projections** and **tail the event stream**.
- Supports **idempotency keys** and **optimistic concurrency**.

---

## Features

- Event-sourced aggregates: Products, Inventory, Orders
- CQRS separation: Commands (writes) vs Reads (queries)
- Projection system with replay:
    ```bash
    php artisan projections:rebuild
    ```
- Event stream tailing:
    ```bash
    php artisan events:tail
    ```
- Idempotency keys (header `Idempotency-Key`)
- Optimistic concurrency (send `expected_version` in body)

---

## Tech Stack

- [Laravel 11](https://laravel.com/)
- PHP 8.2+
- MySQL (or MariaDB)
- Composer

---

## Database Schema

- **event_store**: append-only log of events
- **product_reads**: current state of products
- **inventory_reads**: current stock per product
- **order_reads**: current order status, items, and totals
- **idempotency_keys**: tracks past POST requests to ensure idempotent behavior

---

## Getting Started

### 1. Clone & Install

```bash
git clone https://github.com/segunemmanuel/cqrs-es-shop.git
cd laravel-cqrs-es-shop
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Set up database

Edit `.env` with your DB credentials, then run:

```bash
php artisan migrate
```

### 3. Run server

```bash
php artisan serve
```

---

## API Routes

### Commands

| Method | Endpoint                                           | Description                    |
| ------ | -------------------------------------------------- | ------------------------------ |
| POST   | `/api/commands/products`                           | Create product                 |
| POST   | `/api/commands/inventory/adjust`                   | Adjust inventory for a product |
| POST   | `/api/commands/orders`                             | Create order                   |
| POST   | `/api/commands/orders/{orderId}/items`             | Add item to order              |
| POST   | `/api/commands/orders/{orderId}/items/remove`      | Remove item from order         |
| POST   | `/api/commands/orders/{orderId}/place`             | Place order                    |
| POST   | `/api/commands/orders/{orderId}/cancel`            | Cancel order                   |
| POST   | `/api/commands/orders/{orderId}/payment/authorize` | Mark payment authorized        |

### Reads

| Method | Endpoint                          | Description               |
| ------ | --------------------------------- | ------------------------- |
| GET    | `/api/read/products`              | List all products         |
| GET    | `/api/read/products/{id}`         | Get single product        |
| GET    | `/api/read/inventory/{productId}` | Get inventory for product |
| GET    | `/api/read/orders`                | List all orders           |
| GET    | `/api/read/orders/{id}`           | Get single order          |

---

## Example Usage

**Create product**

```bash
curl -X POST http://127.0.0.1:8000/api/commands/products \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"sku":"SKU-123","name":"Widget","price_cents":1999,"description":"Demo widget"}'
```

**Adjust inventory**

```bash
curl -X POST http://127.0.0.1:8000/api/commands/inventory/adjust \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"product_id":"<product_uuid>","delta":25}'
```

**Idempotency example**

```bash
curl -X POST http://127.0.0.1:8000/api/commands/products \
    -H "Idempotency-Key: abc123" \
    -H "Content-Type: application/json" \
    -d '{"sku":"SKU-999","name":"Idempotent Widget","price_cents":2999}'
```

---

## Postman

Import the provided Postman collection (`cqrs_es_shop_postman_collection.json`).

Recommended flow:

1. Create Product
2. Adjust Inventory
3. Create Order → Add Item → Remove Item → Place Order → Authorize Payment
4. Use `/api/read/...` endpoints to verify state

---

## Rebuild & Tail

- **Rebuild projections** (wipe and replay):
    ```bash
    php artisan projections:rebuild
    ```
- **Tail the event stream**:
    ```bash
    php artisan events:tail
    ```

