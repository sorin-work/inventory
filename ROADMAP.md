# Roadmap

Things I might add to inventory. The app covers the whole loop for a small
shop today (products and variants, several locations, a ledger of every stock
movement, purchase orders, low stock and valuation), so nothing here is
promised or scheduled. These are ideas, written down with enough detail that I
(or anyone else) can pick one up later without starting from zero. Items may
change, move between groups or be dropped.

What the app does today is in the [README](README.md).

- **Next**: small or well understood. I would start with these.
- **Later**: worth doing, but bigger or waiting on a decision.
- **Maybe**: not sure yet that they are worth the extra complexity.

Sizes are rough: **S** is an evening or two, **M** a few evenings up to a
couple of weekends, **L** several weeks of spare time.

Each item is meant to fit in one pull request and to be covered by the test
suite. Pure helpers (money, average cost, barcodes, SKUs, CSV cells) live in
`app/Support` with Pest tests in `tests/Unit`, so new arithmetic worth testing
(landed cost splits, pack conversions, reorder maths) belongs there. Rules that
change stock go through `StockService` or `PurchaseOrderService` in
`app/Services` and are tested in `tests/Feature` against in-memory SQLite, and
pages are Livewire components in `app/Livewire` tested with the Livewire test
helpers in `tests/Feature`. Every shop-owned model uses the `BelongsToShop`
trait, and `tests/Feature/TenancyTest.php` is where a new model proves it
cannot be reached from another shop. Schema changes are new files in
`database/migrations`, and CI runs them, the demo seed and the rollback on
MySQL 8.4, so each one needs a working `down()`.

When an item ships, its row in the Overview says `Shipped` in the Group
column, and its section moves under a `## Shipped` heading at the end, cut
down to one paragraph on what was built and anything left out. The same pull
request updates the README (features, CSV format, limits) and adds an entry
under `## Unreleased` in `CHANGELOG.md`. There is no CHANGELOG yet: the first
item to ship creates it, with that heading.

"Depends on" lists real prerequisites only. Items that merely touch the same
file are not listed there; the bigger overlaps are mentioned in the items.

## Overview

| #   | Item                                          | Group | Area            | Size | Depends on |
| --- | --------------------------------------------- | ----- | --------------- | ---- | ---------- |
| 1   | Mail that is actually sent                    | Next  | ops             | S    | -          |
| 2   | Opening stock from a CSV                      | Next  | import          | M    | -          |
| 3   | Run the tests on MySQL too                    | Next  | tests           | M    | -          |
| 4   | Stocktakes                                    | Next  | stock           | M    | -          |
| 5   | Reverse a movement                            | Next  | stock           | S    | -          |
| 6   | Fix a receipt booked at the wrong cost        | Later | cost            | M    | 5          |
| 7   | Decimal quantities for weighed goods          | Later | stock           | L    | -          |
| 8   | Sales with several lines                      | Later | stock           | M    | -          |
| 9   | Import sales from a till                      | Later | import          | M    | -          |
| 10  | Barcode labels                                | Later | products        | M    | -          |
| 11  | Scan with the phone camera                    | Later | stock           | S    | -          |
| 12  | Purchase orders to send to the supplier       | Later | purchase orders | M    | 1          |
| 13  | Supplier codes and prices                     | Later | suppliers       | M    | -          |
| 14  | Pack sizes: buy by the box, sell by the piece | Later | purchase orders | M    | -          |
| 15  | Returns to the supplier                       | Later | purchase orders | M    | -          |
| 16  | Shipping and other landed costs               | Later | cost            | M    | -          |
| 17  | Reorder points from sales history             | Later | reports         | M    | -          |
| 18  | Sales, best sellers and slow movers           | Later | reports         | M    | -          |
| 19  | Stock and value on a past date                | Later | reports         | M    | -          |
| 20  | Product images                                | Later | products        | M    | -          |
| 21  | Invite staff by email                         | Later | team            | S    | 1          |
| 22  | Staff limited to some locations               | Later | team            | M    | -          |
| 23  | Change log for products and prices            | Later | ops             | M    | -          |
| 24  | Export everything a shop has                  | Later | data            | S    | -          |
| 25  | Scheduled database backups                    | Later | ops             | S    | -          |
| 26  | Logs and error reports for operators          | Later | ops             | S    | -          |
| 27  | Accessibility pass                            | Later | ui              | M    | -          |
| 28  | Bundles and kits                              | Later | products        | L    | 8          |
| 29  | Batches and expiry dates                      | Later | stock           | L    | -          |
| 30  | JSON API with tokens                          | Maybe | api             | L    | -          |
| 31  | Sync with an online shop                      | Maybe | integrations    | L    | 30         |
| 32  | Customer orders and reserved stock            | Maybe | stock           | L    | -          |
| 33  | Serial numbers                                | Maybe | stock           | L    | -          |
| 34  | Faster with thousands of variants             | Maybe | performance     | M    | -          |
| 35  | Two-step sign-in                              | Maybe | security        | M    | -          |
| 36  | Translations and local number formats         | Maybe | ui              | M    | -          |
| 37  | Offline selling at the market stall           | Maybe | stock           | L    | 8          |

## Next

### 1. Mail that is actually sent

The auth pages include forgot password, reset password and email
verification (`resources/views/livewire/pages/auth`), and there are tests for
them, but `.env.example` sets `MAIL_MAILER=log` and docker-compose sets no
mailer at all. In the compose stack a "reset your password" mail ends up in
the log inside the container, so a shop owner who forgets their password is
stuck, and a staff member even more so.

- Add a mailpit service to docker-compose (on a free host port) and point the
  app at it by default, so the links can be clicked in development.
- Document `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`,
  `MAIL_PASSWORD` and `MAIL_FROM_ADDRESS` in the README with an example for a
  normal SMTP server.
- `QUEUE_CONNECTION=sync` means mail is sent inside the request. That is fine
  for a reset link; say so, and leave a queue worker to the items that need
  one (12 sends PDFs).
- Tricky: the login page shows the forgot password link whatever the mail
  setting. With `MAIL_MAILER=log` in production, show "ask your shop owner"
  instead of pretending a mail went out.
- Tests: `PasswordResetTest` already fakes notifications; add one for the
  "mail not configured" message.

### 2. Opening stock from a CSV

The CSV import creates products and variants, but the README says `on_hand`
and `average_cost` "are included in exports but ignored on import". A shop
moving from a spreadsheet has hundreds of products with stock and cost, and
today has to receive each one by hand through `stock/receive` after the
import.

- A separate "Opening stock" import (not a column on the product import, which
  can be run again at any time): columns `sku`, `location`, `quantity` and
  `unit_cost`, matched to existing variants and locations.
- Each row becomes a `receive` through `StockService` with a note
  "Opening stock", so the ledger, `balance_after` and the average cost are
  right from the first day. The same dry run and per-line problems as the
  product import (`ProductImporter`, `ImportResult`, `DryRunFinished`).
- Tricky: running it twice doubles the stock. Refuse rows for a variant and
  location that already has movements, and say so per line. A shop that wants
  to correct stock later uses item 4.
- Tricky: decimal commas and currency symbols in the cost column; `Money` and
  `CsvCell` already parse both, so reuse them.
- Tests: `tests/Feature/Products/OpeningStockImportTest.php` for a clean file,
  an unknown SKU, an unknown location, a second run, and the resulting average
  cost.

### 3. Run the tests on MySQL too

Tests run on in-memory SQLite. The `mysql` CI job only runs the migrations,
the demo seed and the rollback. So the one thing the README promises about
concurrency ("with row locks, so two people selling the last unit can't both
succeed") is never tested: SQLite ignores `lockForUpdate()`, which
`StockService::lockVariant` and `PurchaseOrderService` rely on. Queries that
behave differently on MySQL (case sensitivity in `like`, JSON columns for
option values, strict mode) are not tested either.

- Run `php artisan test` in the `mysql` job as well, with `DB_CONNECTION=mysql`
  (a `phpunit.mysql.xml` or env overrides). Keep SQLite for local runs.
- Add a concurrency test that only runs on MySQL: two connections, each in a
  transaction, try to sell the last unit; exactly one succeeds. Pest's
  `->skip()` when the driver is SQLite.
- Tricky: `RefreshDatabase` on MySQL is slower; `DatabaseTransactions` with a
  migrated schema keeps the job near today's length. The concurrency test
  cannot run inside a wrapping transaction and needs its own clean-up.
- Tests: this item is the tests.

### 4. Stocktakes

`recordCount` sets one variant at one location to what was counted. A real
count goes shelf by shelf over an hour or an afternoon, often by two people,
and the shop wants to see the differences before they are booked.

- First, a race in the current code: `recordCount` reads
  `$variant->quantityAt($location)` before `adjust` opens its transaction and
  takes the lock, then books `counted - current`. A sale in between makes the
  count wrong by that sale. Move the read inside the locked transaction (a
  `setTo` path in `StockService`). That fix is worth shipping on its own.
- A `stocktakes` table (location, status open / posted / cancelled, who
  started it) and `stocktake_lines` (variant, expected when the line was
  first counted, counted, who counted).
- A count page that works with the barcode scanner like the stock forms
  (`PicksVariant`): scan, type the number, next. Scanning the same item twice
  adds to the count, which is how people count mixed shelves.
- A review page with every difference and its value at average cost, then
  "Post", which books an adjustment with reason `StockCount` per line.
- Tricky: sales during a long count. Store the expected quantity when each
  line is counted, and post the difference against the movements since then:
  counted plus sold since counting, not counted against today's level.
- Tricky: items not counted at all. Offer "set uncounted items to zero" as an
  explicit choice, off by default, for full counts.
- Tests: the race fix in `StockServiceTest`; a Feature test for a count with
  a sale in the middle, posting, and cancelling.

### 5. Reverse a movement

The README says mistakes are fixed "with a correcting adjustment, never by
editing history", which is right, but the correcting adjustment is made by
hand: open the adjust form, find the variant, pick the location, work out the
sign, choose "Correction". For a transfer it is two forms.

- A "Reverse" action on a ledger row (`Movements\Index`), for owners and
  staff, that books the opposite movement through `StockService` with a note
  pointing at the original, and a `reverses_id` column so a movement can only
  be reversed once.
- A transfer reverses both halves (they share `transfer_id`). A receipt from
  a purchase order also lowers the line's `quantity_received` and moves the
  order's status back, through `PurchaseOrderService`.
- Tricky: cost. Reversing a sale puts stock back at the cost it left with
  (`unit_cost` on the row). Reversing a receipt takes stock out at the current
  average, which does not undo the receipt's effect on the average; item 6 is
  about that.
- Tricky: stock that has moved on. Reversing a receipt when the stock has
  since been sold fails with the usual "not enough stock" message.
- Tests: in `StockServiceTest` for each movement type, a second reversal, and
  a purchase order receipt.
