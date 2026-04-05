# inventory

Stock tracking for small shops. Products with SKUs, barcodes and variants,
suppliers, several stock locations, a ledger of every stock movement,
reorder points, purchase orders and stock valuation at weighted average
cost. One server-rendered Laravel app.

## Features

- **Shops and roles**: signing up creates a shop, its owner and a first
  location. Owners add staff with a temporary password. Owners can do
  everything; staff handle products, stock and purchase orders but can't see
  the valuation report, change settings, manage the team or delete records.
- **Products and variants**: categories, a preferred supplier, a unit
  (each, pack, kg...) and up to three options such as size and colour.
  Variants can be generated from every combination of option values. Each
  variant has its own SKU, barcode (EAN/UPC check digits are verified),
  price, reorder point and reorder quantity. Products with history are
  archived rather than deleted.
- **Locations**: shop floor, back room, a market stall. One is the default
  for receiving, selling and new purchase orders.
- **Stock movements**: receive, sell, adjust (damaged, lost, expired,
  customer return, correction, other) or record a stock count, and transfer
  between locations. A barcode scanner works as the product picker. Stock can
  never go below zero at a location.
- **Ledger**: every movement is kept with the quantity, the running balance
  at that location, unit cost, who did it and why. Filter per product,
  variant, type, location or date, and export to CSV. Mistakes are fixed
  with a correcting adjustment, never by editing history.
- **Purchase orders**: draft → ordered → partially received → received
  (or cancelled). Receiving books the stock into the order's location at the
  line's unit cost. Expected dates default from the supplier's lead time and
  late orders are flagged.
- **Low stock**: variants at or below their reorder point across all
  locations, with stock already on order taken into account and a suggested
  quantity. Selected rows become one draft purchase order per supplier.
- **Valuation**: stock value at weighted average cost by location, category
  and product, with retail value and margin at list prices.
- **CSV import and export**: export all variants with stock and cost;
  import creates or updates products by SKU, creates missing categories and
  suppliers, and reports problems per line. A dry run checks a file without
  saving. Files saved by Excel with semicolons and decimal commas work.

## How stock and cost work

Stock is kept per variant per location in `stock_levels`, and every change
writes a row to `stock_movements` in the same transaction (with row locks,
so two people selling the last unit can't both succeed). `StockService` is
the only code that changes stock.

Costs use the **weighted average** method. A receipt of `q` units at cost
`c` when `n` units are on hand at average `a` gives a new average of
`(n·a + q·c) / (n + q)`. Sales, adjustments and transfers take stock out at
the current average and leave it unchanged. When stock has run out the next
receipt sets the cost. Averages are stored in cents with four decimals so
they don't drift; money everywhere else is integer cents.

## CSV import format

One row per variant. `product` and `sku` are required; every other column
is optional and can be in any order. Headers are matched loosely, so
`Product Name`, `name` and `title` all work, as do `ean`/`upc` for the
barcode and `reorder qty` for the reorder quantity.

| Column                         | Notes                                                    |
| ------------------------------ | -------------------------------------------------------- |
| `product`                      | Rows with the same name become variants of one product   |
| `sku`                          | Matches existing variants; letters, numbers, `-`, `_`, `.` |
| `barcode`                      | EAN/UPC check digits are verified                        |
| `option1_name`, `option1_value` | Up to three options, e.g. `Size` / `M`                  |
| `category`, `supplier`         | Created if they don't exist yet                          |
| `unit`                         | each, pack, box, pair, kg, g, l, ml or m                 |
| `price`                        | `12.50`, `12,50` and `$12.50` all work                   |
| `reorder_point`, `reorder_quantity` | Whole numbers                                       |
| `description`                  | Free text                                                |

`on_hand` and `average_cost` are included in exports but ignored on import.
Files of up to 5000 rows are accepted.

## Stack

| Part     | Tech                                                           |
| -------- | -------------------------------------------------------------- |
| App      | PHP 8.3, Laravel 12, Livewire 3 (with Volt for auth pages)     |
| UI       | Blade, Alpine.js, Tailwind CSS via Vite                        |
| Database | MySQL 8.4 (SQLite in memory for tests)                         |
| Tests    | Pest 4 with the Laravel and Livewire test helpers              |
| Tooling  | Laravel Pint, GitHub Actions                                   |
| Delivery | Docker Compose: MySQL and one FrankenPHP container for the app |

## Running with Docker

```bash
cp .env.example .env   # optional, the defaults work
docker compose up --build
```

Then open http://localhost:4405 and sign in with the demo account:

- **Email:** `demo@example.com`
- **Password:** `demo-password`

The demo shop, Harbour Lane Goods, sells candles, stationery, ceramics,
textiles and tea from a shop floor, a back room and a Saturday market
stall. It comes with about six months of sales, transfers, adjustments and
purchase orders, a staff member (`maya@example.com` / `demo-password`),
some products running low and orders in every state. Set `SEED_DEMO=false`
to start with an empty database.

| Service | Port (host)           | Notes                                       |
| ------- | --------------------- | ------------------------------------------- |
| app     | http://localhost:4405 | `GET /healthz` checks the app and database  |
| db      | `localhost:5445`      | MySQL 8.4, user/password/database `inventory` |

The ports can be changed with `APP_PORT` and `DB_PORT`. The app container
caches its config, runs migrations and seeds the demo (when enabled) before
it starts serving. Data lives in the `db-data` volume; `docker compose down
-v` removes it.

## Running locally

You need PHP 8.3 with `pdo_mysql` (or `pdo_sqlite`), Composer and Node 22.
The easiest database is the one from Docker:

```bash
docker compose up -d db
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed     # seeds the demo when SEED_DEMO=true
npm install
npm run dev                    # in a second terminal
php artisan serve
```

The app is then on http://localhost:8000. To use SQLite instead, set
`DB_CONNECTION=sqlite` in `.env` and remove the other `DB_` lines.

## Tests and lint

```bash
composer install
php artisan test           # or vendor/bin/pest
vendor/bin/pint --test     # composer lint
```

Tests use an in-memory SQLite database and don't need built assets. The CI
workflow also runs the migrations and the demo seed against MySQL and
builds the Docker image.

## Project layout

```
app/
  Actions/             registering and deleting a shop
  Enums/               roles, movement types, adjustment reasons, PO statuses
  Exceptions/          stock and purchase order rule violations
  Http/Controllers/    health check and CSV downloads
  Livewire/            one component per page, grouped by area
  Models/              Eloquent models; BelongsToShop scopes queries to the user's shop
  Rules/               validation rules
  Services/            StockService, PurchaseOrderService, reports and CSV import/export
  Support/             money, average cost, barcode and SKU helpers
database/
  migrations/
  seeders/DemoSeeder   the demo shop
docker/                entrypoint and PHP settings for the image
resources/views/       Blade layouts, components and Livewire views
tests/
  Feature/             services, reports, pages and Livewire components
  Unit/                pure helpers
```

## License

MIT
