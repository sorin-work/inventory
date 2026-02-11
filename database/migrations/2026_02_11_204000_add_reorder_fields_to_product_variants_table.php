<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->unsignedInteger('reorder_point')->nullable()->after('average_cost');
            $table->unsignedInteger('reorder_quantity')->nullable()->after('reorder_point');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['reorder_point', 'reorder_quantity']);
        });
    }
};
