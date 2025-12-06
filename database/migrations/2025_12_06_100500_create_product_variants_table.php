<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 64);
            $table->string('barcode', 48)->nullable();
            $table->json('option_values')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            // Weighted average cost in cents, kept with four decimals so that
            // repeated receipts don't drift.
            $table->decimal('average_cost', 14, 4)->default(0);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['shop_id', 'sku']);
            $table->unique(['shop_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
