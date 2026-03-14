<?php

namespace Database\Seeders;

use App\Actions\RegisterShop;
use App\Enums\AdjustmentReason;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Shop;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseOrderService;
use App\Services\StockService;
use App\Support\Sku;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A small homewares and gift shop with a back room and a Saturday market
 * stall, about six months of trading history, purchase orders in every
 * state and a handful of products running low.
 */
class DemoSeeder extends Seeder
{
    private Shop $shop;

    private User $owner;

    private User $staff;

    /** @var array<string, Location> */
    private array $locations = [];

    /** @var array<string, Supplier> */
    private array $suppliers = [];

    /** @var Collection<int, ProductVariant> */
    private Collection $variants;

    /** @var array<int, int> variant id => typical unit cost in cents */
    private array $costs = [];

    /** @var list<int> orders left open on purpose */
    private array $keepOpen = [];

    /** The real date; Carbon's clock is moved around while seeding. */
    private Carbon $today;

    public function __construct(
        private readonly StockService $stock,
        private readonly PurchaseOrderService $orders,
    ) {}

    public function run(): void
    {
        $email = config('inventory.demo.email');

        if (User::query()->where('email', $email)->exists()) {
            $this->command?->info("Demo user {$email} already exists, skipping.");

            return;
        }

        mt_srand(20251117);
        $this->today = today();
        $start = $this->today->copy()->subMonths(6)->startOfWeek()->setTime(9, 30);
        Carbon::setTestNow($start);

        try {
            $this->createShop($email);
            $this->createCatalogue();
            $this->openingStock($start);
            $this->trade($start->copy()->addDays(3), $this->today->copy()->subDay());
        } finally {
            Carbon::setTestNow();
        }

        $this->command?->info("Demo shop ready: {$email} / ".config('inventory.demo.password'));
    }

    private function createShop(string $email): void
    {
        $this->owner = app(RegisterShop::class)([
            'shop_name' => 'Harbour Lane Goods',
            'name' => 'Demo Owner',
            'email' => $email,
            'password' => config('inventory.demo.password'),
            'currency' => 'USD',
        ]);
        $this->owner->forceFill(['email_verified_at' => now()])->save();

        $this->shop = $this->owner->shop;

        $this->staff = User::create([
            'shop_id' => $this->shop->id,
            'name' => 'Maya Chen',
            'email' => 'maya@example.com',
            'password' => 'demo-password',
            'role' => Role::Staff,
        ]);

        $main = Location::withoutGlobalScopes()->where('shop_id', $this->shop->id)->firstOrFail();
        $main->update(['name' => 'Shop floor', 'code' => 'FLOOR']);

        $this->locations = [
            'floor' => $main,
            'back' => Location::create(['shop_id' => $this->shop->id, 'name' => 'Back room', 'code' => 'BACK']),
            'market' => Location::create(['shop_id' => $this->shop->id, 'name' => 'Saturday market', 'code' => 'MKT']),
        ];

        $suppliers = [
            'wick' => ['Wick & Wax Co.', 'Hannah Moss', 'orders@wickandwax.example', '+1 503 555 0142', 7],
            'paper' => ['Fold Paper Goods', 'Theo Brandt', 'theo@foldpaper.example', '+1 503 555 0178', 10],
            'clay' => ['Riverbend Ceramics', 'Ana Ruiz', 'studio@riverbend.example', '+1 541 555 0110', 21],
            'cotton' => ['Northwind Textiles', 'Sam Okafor', 'wholesale@northwind.example', '+1 206 555 0199', 14],
            'tea' => ['Leaf Lane Tea', 'Priya Nair', 'trade@leaflane.example', '+1 503 555 0133', 5],
        ];

        foreach ($suppliers as $key => [$name, $contact, $mail, $phone, $lead]) {
            $this->suppliers[$key] = Supplier::create([
                'shop_id' => $this->shop->id,
                'name' => $name,
                'contact_name' => $contact,
                'email' => $mail,
                'phone' => $phone,
                'lead_time_days' => $lead,
            ]);
        }

        $this->suppliers['paper']->update(['notes' => 'Minimum order $150. Net 30.']);
        $this->suppliers['clay']->update(['notes' => 'Small batches, colours vary slightly between firings.']);
    }

    private function createCatalogue(): void
    {
        $categories = collect(['Candles', 'Stationery', 'Ceramics', 'Textiles', 'Tea', 'Gifts'])
            ->mapWithKeys(fn ($name) => [$name => Category::create(['shop_id' => $this->shop->id, 'name' => $name])]);

        // name, category, supplier, price, cost, reorder point, reorder qty, options, barcode prefix
        $catalogue = [
            ['Soy candle, cedar & sage', 'Candles', 'wick', 2400, 950, 6, 12, null],
            ['Soy candle, fig leaf', 'Candles', 'wick', 2400, 950, 6, 12, null],
            ['Soy candle, sea salt', 'Candles', 'wick', 2400, 950, 6, 12, null],
            ['Travel tin candle', 'Candles', 'wick', 1200, 420, 10, 24, ['Scent' => ['Cedar', 'Fig', 'Sea salt']]],
            ['Beeswax taper pair', 'Candles', 'wick', 1400, 560, 8, 20, ['Colour' => ['Natural', 'Ochre', 'Forest']]],
            ['Matches in a jar', 'Candles', 'wick', 800, 260, 10, 30, null],
            ['Linen notebook A5', 'Stationery', 'paper', 1800, 700, 8, 20, ['Paper' => ['Lined', 'Dot grid', 'Blank']]],
            ['Pocket notebook, set of 3', 'Stationery', 'paper', 1200, 450, 6, 18, null],
            ['Weekly desk planner', 'Stationery', 'paper', 2200, 900, 4, 10, null],
            ['Greeting card', 'Stationery', 'paper', 550, 180, 20, 50, ['Design' => ['Birthday', 'Thank you', 'Blank', 'Congratulations']]],
            ['Brass pen', 'Stationery', 'paper', 3200, 1400, 3, 6, null],
            ['Wrapping paper roll', 'Stationery', 'paper', 900, 300, 10, 25, ['Print' => ['Botanical', 'Stripe']]],
            ['Stoneware mug', 'Ceramics', 'clay', 2800, 1150, 6, 12, ['Glaze' => ['Speckled white', 'Sea green', 'Charcoal']]],
            ['Small serving bowl', 'Ceramics', 'clay', 3600, 1500, 3, 6, ['Glaze' => ['Speckled white', 'Sea green']]],
            ['Bud vase', 'Ceramics', 'clay', 2200, 850, 4, 8, null],
            ['Ring dish', 'Ceramics', 'clay', 1600, 600, 5, 10, null],
            ['Linen tea towel', 'Textiles', 'cotton', 1600, 620, 8, 20, ['Colour' => ['Oat', 'Sage', 'Rust']]],
            ['Canvas tote', 'Textiles', 'cotton', 2000, 750, 6, 15, ['Colour' => ['Natural', 'Black']]],
            ['Wool beanie', 'Textiles', 'cotton', 3200, 1300, 3, 8, ['Size' => ['S/M', 'L/XL'], 'Colour' => ['Grey', 'Navy', 'Mustard']]],
            ['Cotton apron', 'Textiles', 'cotton', 3800, 1600, 2, 6, ['Size' => ['Regular', 'Long']]],
            ['Breakfast blend, loose leaf 100g', 'Tea', 'tea', 1300, 480, 10, 24, null],
            ['Earl grey, loose leaf 100g', 'Tea', 'tea', 1400, 520, 10, 24, null],
            ['Chamomile, loose leaf 50g', 'Tea', 'tea', 1100, 400, 8, 20, null],
            ['Tea infuser', 'Tea', 'tea', 900, 310, 6, 12, null],
            ['Gift box, small', 'Gifts', null, 600, 180, 10, 30, null],
            ['Gift card holder', 'Gifts', null, 300, 60, 20, 50, null],
            ['Dried flower bunch', 'Gifts', null, 1800, 700, null, null, ['Colour' => ['Pastel', 'Autumn']]],
        ];

        $variants = collect();
        $barcode = 501234500000;

        foreach ($catalogue as [$name, $category, $supplier, $price, $cost, $point, $qty, $options]) {
            $product = Product::create([
                'shop_id' => $this->shop->id,
                'category_id' => $categories[$category]->id,
                'supplier_id' => $supplier ? $this->suppliers[$supplier]->id : null,
                'name' => $name,
                'unit' => str_contains($name, 'pair') ? 'pair' : (str_contains($name, 'set of') ? 'pack' : 'each'),
                'option_names' => $options ? array_keys($options) : null,
            ]);

            $combinations = $this->combinations($options ?? []);

            foreach ($combinations as $position => $values) {
                $barcode++;
                $variant = ProductVariant::create([
                    'shop_id' => $this->shop->id,
                    'product_id' => $product->id,
                    'sku' => Sku::suggest($this->shortName($name), $values),
                    'barcode' => $category === 'Gifts' ? null : $this->ean13((string) $barcode),
                    'option_values' => $values === [] ? null : $values,
                    'price_cents' => $price,
                    'reorder_point' => $point,
                    'reorder_quantity' => $qty,
                    'position' => $position,
                ]);
                $this->costs[$variant->id] = $cost;
                $variants->push($variant);
            }
        }

        $this->variants = $variants;

        Product::create([
            'shop_id' => $this->shop->id,
            'category_id' => $categories['Candles']->id,
            'supplier_id' => $this->suppliers['wick']->id,
            'name' => 'Pumpkin spice candle (seasonal)',
            'archived_at' => now(),
        ])->variants()->create(['shop_id' => $this->shop->id, 'sku' => 'CANDLE-PUMPKIN', 'price_cents' => 2400]);
    }

    /**
     * The first deliveries, booked through received purchase orders so the
     * history starts the way a real shop's would.
     */
    private function openingStock(Carbon $start): void
    {
        foreach ($this->variants->groupBy(fn ($v) => $v->product->supplier_id ?? 0) as $supplierId => $variants) {
            $lines = $variants->map(fn ($v) => [
                'product_variant_id' => $v->id,
                'quantity_ordered' => max(6, ($v->reorder_quantity ?? 10) + mt_rand(4, 12)),
                'unit_cost_cents' => $this->costs[$v->id],
            ])->values()->all();

            if ($supplierId === 0) {
                foreach ($variants as $variant) {
                    $this->stock->receive($variant, $this->locations['floor'], mt_rand(12, 30), $this->costs[$variant->id], $this->owner, 'Opening stock');
                }

                continue;
            }

            $order = $this->orders->createDraft($this->shop, Supplier::withoutGlobalScopes()->findOrFail($supplierId), $this->locations['back'], $lines, [], $this->owner);
            $this->orders->markOrdered($order);
            $this->orders->receive($order, $order->lines->mapWithKeys(fn ($l) => [$l->id => $l->quantity_ordered])->all(), $this->owner);
        }

        // Fill the shop floor from the back room.
        Carbon::setTestNow($start->copy()->addDay()->setTime(10, 15));

        foreach ($this->variants as $variant) {
            $inBack = $variant->quantityAt($this->locations['back']);

            if ($inBack > 2) {
                $this->stock->transfer($variant, $this->locations['back'], $this->locations['floor'], (int) ceil($inBack * 0.6), $this->owner, 'Stocking the shelves');
            }
        }
    }

    private function trade(Carbon $from, Carbon $to): void
    {
        $orderedThisWeek = [];
        $countDay = $to->copy()->subWeeks(5)->startOfWeek()->addDays(2);
        $ordersDay = $this->tradingDay($this->today->copy()->subDays(9));
        $deliveryDay = $this->tradingDay($this->today->copy()->subDays(2));
        $lastDay = $this->tradingDay($to->copy());

        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $isSaturday = $day->isSaturday();

            if ($day->isSunday()) {
                $orderedThisWeek = [];

                continue;
            }

            $this->receiveDueOrders($day);

            if ($day->isSameDay($deliveryDay)) {
                $this->partialDelivery($day);
            }

            // Saturdays: take a box of stock to the market in the morning.
            if ($isSaturday) {
                Carbon::setTestNow($day->copy()->setTime(7, 40));

                foreach ($this->variants->random(8) as $variant) {
                    $available = $variant->quantityAt($this->locations['back']);

                    if ($available >= 4) {
                        $this->stock->transfer($variant, $this->locations['back'], $this->locations['market'], min(4, $available - 1), $this->staff, null);
                    }
                }
            }

            $location = $isSaturday ? $this->locations['market'] : $this->locations['floor'];
            $times = collect(range(1, mt_rand($isSaturday ? 8 : 3, $isSaturday ? 16 : 9)))
                ->map(fn () => $isSaturday ? mt_rand(8 * 60, 15 * 60 + 59) : mt_rand(10 * 60, 17 * 60 + 29))
                ->sort();

            foreach ($times as $minutes) {
                Carbon::setTestNow($day->copy()->startOfDay()->addMinutes($minutes));
                $variant = $this->variants->random();
                $qty = mt_rand(1, 10) > 8 ? 2 : 1;

                if ($variant->quantityAt($location) >= $qty) {
                    $this->stock->sell($variant, $location, $qty, null, mt_rand(0, 1) ? $this->staff : $this->owner);
                }
            }

            // Market leftovers go back at the end of the day.
            if ($isSaturday) {
                Carbon::setTestNow($day->copy()->setTime(16, 30));

                foreach ($this->variants as $variant) {
                    $left = $variant->quantityAt($this->locations['market']);

                    if ($left > 0) {
                        $this->stock->transfer($variant, $this->locations['market'], $this->locations['back'], $left, $this->staff, 'Back from the market');
                    }
                }
            }

            if (mt_rand(1, 12) === 1) {
                $this->randomAdjustment($day);
            }

            // Restock the floor when it runs thin.
            if (! $isSaturday && mt_rand(1, 3) === 1) {
                Carbon::setTestNow($day->copy()->setTime(18, 10));

                foreach ($this->variants as $variant) {
                    $onFloor = $variant->quantityAt($this->locations['floor']);
                    $inBack = $variant->quantityAt($this->locations['back']);

                    if ($onFloor < 3 && $inBack > 0) {
                        $this->stock->transfer($variant, $this->locations['back'], $this->locations['floor'], min($inBack, 6), $this->staff, 'Restock');
                    }
                }
            }

            // A stock take on the shop floor, a few weeks back.
            if ($day->isSameDay($countDay)) {
                Carbon::setTestNow($day->copy()->setTime(19, 0));

                foreach ($this->variants->random(6) as $variant) {
                    $current = $variant->quantityAt($this->locations['floor']);
                    $counted = max(0, $current + mt_rand(-2, 1));

                    if ($counted !== $current) {
                        $this->stock->recordCount($variant, $this->locations['floor'], $counted, $this->owner, 'Quarterly count');
                    }
                }
            }

            // Mondays: reorder whatever is low, deliveries arrive later.
            if ($day->isMonday() && $day->lt($to->copy()->subWeeks(3))) {
                $this->weeklyReorder($day, $orderedThisWeek);
            }

            if ($day->isSameDay($ordersDay)) {
                $this->openOrders();
            }

            if ($day->isSameDay($lastDay)) {
                $this->lastEvening($day);
            }
        }
    }

    private function randomAdjustment(Carbon $day): void
    {
        Carbon::setTestNow($day->copy()->setTime(18, 5));

        $variant = $this->variants->random();
        $location = $this->locations['floor'];
        $onHand = $variant->quantityAt($location);

        $reason = collect([AdjustmentReason::Damaged, AdjustmentReason::Damaged, AdjustmentReason::Lost, AdjustmentReason::Returned])->random();

        if ($reason === AdjustmentReason::Returned) {
            $this->stock->adjust($variant, $location, 1, $reason, $this->staff, 'Customer changed their mind');
        } elseif ($onHand > 0) {
            $notes = [
                AdjustmentReason::Damaged->value => ['Chipped in transit', 'Dropped while restocking', 'Water damage on the box'],
                AdjustmentReason::Lost->value => ['Missing after weekend', 'Could not find on shelf'],
            ];
            $this->stock->adjust($variant, $location, -1, $reason, $this->staff, collect($notes[$reason->value])->random());
        }
    }

    /**
     * @param  array<int, bool>  $orderedThisWeek
     */
    private function weeklyReorder(Carbon $day, array &$orderedThisWeek): void
    {
        Carbon::setTestNow($day->copy()->setTime(20, 30));

        $low = $this->variants->filter(function (ProductVariant $variant) {
            $supplierId = $variant->product->supplier_id;

            return $supplierId !== null
                && $variant->reorder_point !== null
                && $variant->onHand() <= $variant->reorder_point
                && ! $this->hasOpenOrder($variant);
        });

        foreach ($low->groupBy(fn ($v) => $v->product->supplier_id) as $supplierId => $variants) {
            if (isset($orderedThisWeek[$supplierId])) {
                continue;
            }

            $orderedThisWeek[$supplierId] = true;
            $supplier = Supplier::withoutGlobalScopes()->findOrFail($supplierId);

            $order = $this->orders->createDraft(
                $this->shop,
                $supplier,
                $this->locations['back'],
                $variants->map(fn ($v) => [
                    'product_variant_id' => $v->id,
                    'quantity_ordered' => $v->reorder_quantity ?? 10,
                    'unit_cost_cents' => (int) round($this->costs[$v->id] * (1 + mt_rand(-3, 8) / 100)),
                ])->values()->all(),
                ['expected_on' => $day->copy()->addDays($supplier->lead_time_days)->toDateString()],
                $this->owner,
            );

            $this->orders->markOrdered($order);
        }
    }

    private function receiveDueOrders(Carbon $day): void
    {
        $due = $this->shop->purchaseOrders()->withoutGlobalScopes()
            ->where('shop_id', $this->shop->id)
            ->open()
            ->whereDate('expected_on', '<=', $day)
            ->whereNotIn('id', $this->keepOpen)
            ->with('lines')
            ->get();

        foreach ($due as $order) {
            Carbon::setTestNow($day->copy()->setTime(9, mt_rand(0, 50)));

            // Now and then a supplier ships short and sends the rest later.
            $short = mt_rand(1, 5) === 1 && $order->status->value === 'ordered';

            $quantities = $order->lines->mapWithKeys(fn ($line) => [
                $line->id => $short ? (int) floor($line->remaining() / 2) : $line->remaining(),
            ])->filter()->all();

            if ($quantities === []) {
                $quantities = $order->lines->mapWithKeys(fn ($line) => [$line->id => $line->remaining()])->filter()->all();
            }

            $this->orders->receive($order, $quantities, $this->staff);

            if ($short) {
                $order->update(['expected_on' => $day->copy()->addDays(4)->toDateString()]);
            }
        }
    }

    /**
     * Leaves the shop with an order on the way, one that's late, one
     * partly delivered and a draft, so every state shows up in the UI.
     */
    private function openOrders(): void
    {
        Carbon::setTestNow(Carbon::now()->setTime(20, 15));

        $late = $this->demoOrder('clay', 4, -3);
        $soon = $this->demoOrder('tea', 3, 4);
        $partial = $this->demoOrder('cotton', 4, 2);

        foreach ([$late, $soon, $partial] as $order) {
            $this->orders->markOrdered($order);
            $this->keepOpen[] = $order->id;
        }
    }

    private function partialDelivery(Carbon $day): void
    {
        Carbon::setTestNow($day->copy()->setTime(9, 5));

        $order = $this->shop->purchaseOrders()->withoutGlobalScopes()
            ->whereIn('id', $this->keepOpen)
            ->where('supplier_id', $this->suppliers['cotton']->id)
            ->with('lines')
            ->first();

        if ($order !== null) {
            $first = $order->lines->first();
            $this->orders->receive($order, [$first->id => $first->quantity_ordered], $this->staff);
        }
    }

    private function lastEvening(Carbon $day): void
    {
        Carbon::setTestNow($day->copy()->setTime(21, 0));

        $draft = $this->demoOrder('paper', 3, null);
        $draft->update(['notes' => 'Ask Theo about the new spring card designs.']);

        // Something unusual to explain in the ledger.
        $variant = $this->variants->first(fn ($v) => $v->quantityAt($this->locations['floor']) > 2);

        if ($variant !== null) {
            $this->stock->adjust($variant, $this->locations['floor'], -2, AdjustmentReason::Other, $this->owner, 'Used for the window display');
        }
    }

    private function demoOrder(string $supplier, int $count, ?int $expectedInDays): PurchaseOrder
    {
        $variants = $this->variants
            ->filter(fn ($v) => $v->product->supplier_id === $this->suppliers[$supplier]->id)
            ->take($count);

        return $this->orders->createDraft(
            $this->shop,
            $this->suppliers[$supplier],
            $this->locations['back'],
            $variants->map(fn ($v) => [
                'product_variant_id' => $v->id,
                'quantity_ordered' => $v->reorder_quantity ?? 10,
                'unit_cost_cents' => $this->costs[$v->id],
            ])->values()->all(),
            ['expected_on' => $expectedInDays === null ? null : $this->today->copy()->addDays($expectedInDays)->toDateString()],
            $this->owner,
        );
    }

    private function tradingDay(Carbon $day): Carbon
    {
        return $day->isSunday() ? $day->subDay() : $day;
    }

    private function hasOpenOrder(ProductVariant $variant): bool
    {
        return PurchaseOrderLine::query()
            ->where('product_variant_id', $variant->id)
            ->whereHas('purchaseOrder', fn ($q) => $q->withoutGlobalScopes()->open())
            ->exists();
    }

    /**
     * @param  array<string, list<string>>  $options
     * @return list<list<string>>
     */
    private function combinations(array $options): array
    {
        $combinations = [[]];

        foreach ($options as $values) {
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $next[] = [...$combination, $value];
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    private function shortName(string $name): string
    {
        return trim(preg_replace('/\b(loose leaf|set of \d|in a jar|pair|small|\d+g)\b/i', '', explode(',', $name)[0].(str_contains($name, ',') ? ' '.trim(explode(',', $name)[1]) : '')));
    }

    private function ean13(string $twelveDigits): string
    {
        $sum = 0;

        foreach (str_split($twelveDigits) as $i => $digit) {
            $sum += (int) $digit * ($i % 2 === 0 ? 1 : 3);
        }

        return $twelveDigits.((10 - $sum % 10) % 10);
    }
}
