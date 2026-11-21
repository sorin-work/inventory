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

## Later

### 6. Fix a receipt booked at the wrong cost

A receipt typed as 150.00 instead of 15.00 raises the weighted average cost
of that variant, and every sale after it is booked at the wrong cost. A
correcting adjustment fixes the quantity, never the cost, so the valuation and
margins stay wrong until the stock runs out.

- A "Correct cost" action on a receipt: enter the right unit cost, and the
  average is recalculated from that receipt forward by replaying the later
  movements of that variant (`AverageCost` already has the formula).
- The original row is kept; the correction writes a cost-only movement
  (quantity 0, a new `cost_correction` type) with the old and new average, so
  the ledger still shows what happened.
- Tricky: sales after the receipt went out at the wrong cost. Their stored
  `unit_cost` is history; recalculating them changes past margins. I would
  leave them and show the cost correction as its own line in the valuation,
  and state it in the docs.
- Undecided: whether staff may do this. It changes the valuation report that
  only owners can see, so owners only.
- Tests: `AverageCostTest` for the replay; a Feature test with a receipt, a
  sale and a correction.

### 7. Decimal quantities for weighed goods

Products can have the unit kg, g, l, ml or m, but every quantity is an
integer: `stock_levels.quantity`, `stock_movements.quantity` and
`balance_after`, and the purchase order line quantities. Selling 0.25 kg of
loose tea is impossible; the workaround is to use grams and treat 250 as the
quantity, which makes prices per gram.

- Store quantities as `decimal(14, 3)` in all those columns, and handle them
  as strings or integers of thousandths in PHP, never floats. A small
  `Quantity` helper in `app/Support` next to `Money`.
- Units decide the allowed precision: `each`, `pack`, `box`, `pair` stay whole
  numbers; weights and volumes take three decimals.
- `AverageCost`, valuation and reorder maths take the new type.
- Tricky: the migration touches the biggest tables. On MySQL, changing a
  column type rewrites the table; fine for a shop's ledger, but test the
  rollback in the CI job.
- Tricky: CSV import and export, and every form that reads `int` quantities.
- Tests: `QuantityTest`; `StockServiceTest` with decimal sales; the import
  with `0,25`.

### 8. Sales with several lines

`Stock\Sell` books one variant per form. A customer buying five things at the
market stall means five saves, and the ledger shows them as unrelated rows.

- A basket on the sell page: scan or pick variants, change quantities and
  prices, then "Book sale". Each line is a `sell` through `StockService`, all
  in one transaction, sharing a `sale_id` (like `transfer_id` for transfers).
- The ledger can group by sale, and the CSV export carries the id.
- Tricky: one line out of stock fails the whole sale; show which line, and
  keep the basket.
- Undecided: discounts on the whole sale. I would allow a price per line only;
  anything more is a till, and the app is not one.
- Tests: Livewire test for the basket; service test for all or nothing.

### 9. Import sales from a till

Most shops already sell through a till or a card terminal app that can export
the day's sales as CSV. Typing them again into the sell form is the main
reason stock drifts.

- A sales import with `sku`, `quantity`, `price` and an optional `date` and
  `location`, matched like the product import, booked as item 8's grouped
  sales (or single `sell` calls until then).
- Saved column mappings per source, because every till names its columns
  differently.
- Tricky: imported dates in the past. Movements are stored with `created_at`
  as now; a past date would put rows out of order and break `balance_after`.
  I would book them now and keep the till's date in the note, and say so.
- Tricky: an export imported twice. Keep a hash of each imported file and
  warn.
- Tests: importer tests with a sample file, a repeat file and an unknown SKU.

### 10. Barcode labels

The app checks EAN and UPC check digits (`Barcode`) and the scanner works as
a picker, but products without a barcode need one printed, and shelf labels
with the price are the other everyday need.

- Generate internal barcodes (Code 128 of the SKU, or EAN-13 with a "20"
  in-store prefix) for variants that have none.
- A print page: choose variants and how many labels, pick a label size
  (A4 sheets of 24 or 65, or a 62 mm roll), and print from the browser. The
  barcode as inline SVG drawn by a small PHP class, no external service.
- After receiving a purchase order, offer labels for what was received.
- Tests: unit tests for the generated codes and check digits; a Feature test
  that the print page renders the right number of labels.

### 11. Scan with the phone camera

The barcode scanner works because USB scanners type like a keyboard. On a
phone at the market stall there is no scanner.

- A camera button next to the variant picker that uses the browser's
  `BarcodeDetector` where it exists (Chrome on Android), falling back to a
  small bundled library (for example ZXing's browser build) elsewhere, and
  fills the same field the scanner fills.
- Tricky: camera access needs HTTPS; say so in the README.
- Tests: none for the camera itself; the field handling is already tested.

### 12. Purchase orders to send to the supplier

A purchase order goes from draft to ordered, but there is nothing to send:
the owner copies the lines into an email.

- A printable purchase order page (shop and supplier addresses, lines, totals,
  expected date) and a PDF of it (with `dompdf` or similar, bundled).
- "Send to supplier" mails the PDF to the supplier's email through item 1's
  mail, and marks the order as ordered.
- Tricky: PDF rendering in the request is slow for long orders. With
  `QUEUE_CONNECTION=sync` it still works; a queue worker would be the first
  reason to run one, in the same container with a supervisor or a second
  compose service.
- Tests: a Feature test that the mail is queued with an attachment.

### 13. Supplier codes and prices

A product has one preferred supplier, and the purchase order line cost
defaults from the variant. Suppliers have their own product codes, and the
same item is often bought from two suppliers at different prices.

- `supplier_variants` (supplier, variant, supplier code, last cost, minimum
  order quantity). Purchase order lines show the supplier code and default to
  that supplier's last cost, updated on each receipt.
- The low stock report suggests the supplier with the last receipt, or the
  preferred one.
- Tests: service tests for the defaults and the update on receipt.

### 14. Pack sizes: buy by the box, sell by the piece

Candles come in boxes of 12 and are sold one by one. Today the purchase order
is in pieces and the cost per piece is worked out by hand.

- A pack size per supplier variant (item 13's table, or on the variant until
  then). Purchase order lines can be entered in packs, with a pack cost;
  receiving books pieces at the pack cost divided by the pack size.
- The conversion lives in `app/Support` and rounds the unit cost to the four
  decimals `average_cost` keeps.
- Tests: unit tests for the conversion; a purchase order received in packs.

### 15. Returns to the supplier

Damaged deliveries go back to the supplier. Today that is an adjustment with
reason "damaged", which loses the link to the order and to the money owed back.

- A "Return to supplier" action on a received order: pick lines and
  quantities; stock goes out at the cost it came in at, and the order shows a
  returned quantity and value.
- A new movement type `supplier_return` so reports can tell it from losses.
- Tests: service test for stock and cost; the order page shows the return.

### 16. Shipping and other landed costs

A delivery with a shipping charge makes every item cost more than its line
price. The app books only the line cost, so margins look better than they are.

- Extra costs on a purchase order (shipping, duty), spread over the received
  lines by value or by quantity, and added to the unit cost of the receipt.
- Tricky: a charge that arrives after the goods. Adding it later changes the
  average cost after the fact, which is item 6's replay again. I would allow
  it only before the order is fully received at first.
- Tests: unit tests for the split and its rounding (the parts add up to the
  total); a received order with shipping.

### 17. Reorder points from sales history

Reorder point and quantity are set by hand per variant. Few shops keep them
up to date, so the low stock report is only as good as a guess made months
ago.

- A suggestion per variant from the last 90 days of sales: average daily
  sales times the supplier's `lead_time_days`, plus a safety margin, and a
  reorder quantity covering a chosen number of days.
- Shown next to the current values with an "apply" per row or for a
  selection; never changed without asking.
- Tricky: new and seasonal products. Show nothing for products with less than
  a few weeks of history, and say how many days the suggestion is based on.
- Tests: unit tests for the maths in `app/Support`; a Feature test for apply.

### 18. Sales, best sellers and slow movers

Sales are recorded with their price (`unit_price_cents`) and cost
(`unit_cost`), so the data for a sales report is there, but the only reports
are low stock and valuation.

- A sales report for a period: revenue, cost of goods, margin, by product,
  category and location, and a CSV export like the other reports.
- Best sellers and slow movers: variants ranked by units or margin, and
  variants with stock and no sale in N days (dead stock tying up money).
- Tricky: owners only, like valuation, because it shows cost.
- Tests: `tests/Feature/Reports` with known movements.

### 19. Stock and value on a past date

The valuation shows today. Year-end accounts need the stock and its value on
31 December, which today means running the report on the right evening.

- Stock on a date is the last `balance_after` per variant and location on or
  before it, which the ledger already has (indexed by variant and time).
- Value on a date needs the average cost at that time. Each movement stores
  its `unit_cost`, but receipts change the average, so store `average_after`
  on each movement from now on, and backfill it in the migration by replaying
  history per variant.
- Tests: a report for a past date with receipts and sales either side.

### 20. Product images

Products have a name, description and options, but no picture. In a shop
with many similar items (candle scents, mug glazes) a thumbnail in the picker
and the product list saves mistakes.

- One image per product (and optionally per variant), stored on a local disk
  volume, resized on upload to a thumbnail and a medium size.
- Tricky: the Docker image has no volume for uploads yet; add one to
  docker-compose and to the backup item.
- Tricky: accept only JPEG, PNG and WebP, check the content and not the name,
  and serve through a route that checks the shop, like everything else.
- Tests: upload, resize and the tenancy check.

### 21. Invite staff by email

Owners add staff with a temporary password they choose and pass on by hand.
Nothing makes the staff member change it.

- With mail configured (item 1), "Invite" sends a link to set a password
  instead. Without mail, the current way stays.
- For the temporary password path, a `must_change_password` flag that sends
  the user to the profile page until they change it.
- Tests: Feature tests for both paths.

### 22. Staff limited to some locations

Staff see and move stock in every location. A shop with a market stall run
by a weekend helper may want that helper to sell from the stall only.

- An optional list of locations per staff member; empty means all. Stock forms
  only offer those, and `StockService` refuses others for that user.
- Tricky: transfers between an allowed and a disallowed location. Allow
  transfers out of an allowed location only.
- Tests: `RolesTest` cases per form.

### 23. Change log for products and prices

Stock changes are in the ledger, with who and why. Price changes, reorder
points, archived products and settings leave no trace, and "who changed the
price of this?" has no answer.

- An `activity_log` table (actor, model, field, old and new value, time)
  written from model events on products, variants, suppliers and locations,
  and a "History" tab on the product page.
- Tests: a Feature test that a price change writes one row with both values.

### 24. Export everything a shop has

There are CSV exports for products, movements, low stock and valuation. An
owner leaving the app, or wanting their own backup, has no way to take the
suppliers, locations, purchase orders and the whole ledger in one go.
`DeleteShop` removes everything, so the export should come first.

- "Export all data" in shop settings: a zip with one CSV per table, the same
  formats as the existing exports where they exist.
- Offer it on the delete shop page before the final confirmation.
- Tests: a Feature test that the zip has every file and only this shop's
  rows.

### 25. Scheduled database backups

All data lives in the `db-data` volume, and the README explains
`docker compose down -v`, not how to keep the data.

- A `backup` service in docker-compose that runs `mysqldump
  --single-transaction` nightly into a mounted folder and keeps the last 14,
  and a restore section in the README.
- Tricky: once item 20 adds an images volume, the backup needs it too.
- Tests: none in the suites; a dump and restore by hand in the PR.

### 26. Logs and error reports for operators

Laravel logs to a file inside the container by default. Someone running the
compose stack has to exec into the container to see why a page failed.

- Log to `stderr` in the container (`LOG_CHANNEL=stderr`) so `docker compose
  logs` shows everything, with JSON formatting as an option.
- `/healthz` already checks the database; add a check that the storage folder
  is writable, which is the usual FrankenPHP permission problem.
- Tests: `HealthTest` for the storage check.

### 27. Accessibility pass

There is no record of an accessibility review. The stock forms are used fast
and often, which is where problems hurt most.

- Keyboard path through the variant picker and the stock forms, focus
  returned to the picker after each save, errors linked to their fields with
  `aria-describedby` (the `field` component is the place).
- Flash messages announced through a live region; badges with text, not only
  colour.
- Tests: Livewire tests for the focus target where possible; a manual pass
  with a screen reader recorded in the PR.

### 28. Bundles and kits

Gift sets and hampers are sold as one product but made of others. Selling a
"tea gift box" should take out one tin of tea, one mug and one box.

- A bundle is a variant with components (variant and quantity). Selling it
  sells each component in one transaction (item 8's grouping), at each
  component's cost; the bundle itself holds no stock.
- Available quantity of a bundle is the minimum over its components; the low
  stock report checks components, not bundles.
- Tricky: assembled kits that are made ahead and do hold stock are a
  different thing (an "assemble" movement). Start with bundles only.
- Tests: service tests for selling a bundle and running out of one component.

### 29. Batches and expiry dates

The adjustment reasons include "expired", but nothing knows when anything
expires. Tea, candles with scent oils and food all have dates.

- Optional batch tracking per product: receipts record a batch code and an
  expiry date, stock is kept per batch within a location, sales take the
  earliest expiry first unless one is picked.
- An "expiring soon" report.
- Tricky: this changes `stock_levels` from variant and location to variant,
  location and batch, which touches every stock path. Products without batch
  tracking must keep working unchanged.
- Tests: `StockServiceTest` for first-expiry-first sales and transfers.

## Maybe

### 30. JSON API with tokens

Everything is server-rendered Livewire, and the only machine-readable output
is CSV downloads. Tills, scripts and online shops need an API.

- Laravel Sanctum tokens per user, created on the profile page with a name
  and read-only or full scope, and a small versioned API: list variants with
  stock, book a sale, receive stock, all through `StockService`.
- Tricky: tenancy. The `BelongsToShop` scope reads the signed-in user, so
  token requests need the same user resolution, and `TenancyTest` should get
  API cases.
- Why maybe: until there is something on the other side, it is unused
  surface. Item 9's till import covers the most common need without it.

### 31. Sync with an online shop

Shops that also sell online want one stock count for both.

- Start with self-hosted WooCommerce: its webhooks call item 30's API to book
  online sales, and stock changes here push the new level back through its
  REST API.
- Why maybe: every platform is different, and two-way sync has hard edges
  (an online sale and a shop sale of the last unit at the same moment). It
  also needs a queue worker and retries.

### 32. Customer orders and reserved stock

Special orders and click-and-collect hold stock for a customer before it is
sold. Today that stock looks available until it is sold.

- Orders with lines that reserve stock at a location; available is on hand
  minus reserved; fulfilling books the sale.
- Why maybe: it is the start of an order system, which is a different app.
  A "reserved" adjustment to a hidden location is a workable stopgap.

### 33. Serial numbers

For shops selling items with serial numbers (electronics, bikes), each unit
matters, not just the count.

- Optional per product: receipts record a serial per unit, sales pick one.
- Why maybe: the demo shop and most small shops do not need it, and it
  changes stock from counts to units for those products.

### 34. Faster with thousands of variants

The app is quick with the demo shop. A few places load everything:
`LowStockReport::rows` gets every variant with its levels and filters in PHP,
and the valuation groups in PHP after a query. With 20,000 variants both would
be slow.

- Push the low stock comparison into SQL (sum of levels against the reorder
  point, with open order quantities from a subquery) and page it.
- Why maybe: nothing is slow today. Measure with a generated shop first
  (a seeder option for size) and fix what is slow.

### 35. Two-step sign-in

Owners can see costs and margins and delete the shop.

- TOTP with recovery codes for any user, required for owners when the shop
  turns it on. Laravel Fortify has this; the app uses Breeze-style Volt pages,
  so it means either adding Fortify for this alone or a small TOTP class.
- Why maybe: login is already rate limited (`LoginForm` allows five attempts),
  and the risk for a small shop is modest.

### 36. Translations and local number formats

All text is English. Prices already parse decimal commas on import, but the
pages show `12.50` everywhere.

- Laravel's translation files for the UI, and number and date formatting from
  a shop locale setting next to the currency.
- Why maybe: a lot of strings for a single maintainer, with no translator.
  Local number formats alone are S and could go first.

### 37. Offline selling at the market stall

The demo shop sells at a Saturday market, where the connection is often bad.
A Livewire page needs the server for every action.

- A small offline sell page (a service worker and IndexedDB) that queues
  sales with a client id and books them through item 8's sale path when back
  online, failing loudly for anything out of stock by then.
- Why maybe: it is a second front end beside Livewire, and conflicts (the
  last unit sold in the shop while the stall was offline) need a clear answer.
  Item 9's till import may be the simpler fix.
