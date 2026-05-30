<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();

            // Owner
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Identifiers
            $table->string('sales_id')->unique(); // SO-YYYYMMDD-0001

            $table->foreignId('buy_order_id')
                ->constrained('buy_orders')
                ->cascadeOnDelete();

            // Item info
            $table->string('item_name');
            $table->string('tier', 10);

            // Quantities
            $table->unsignedInteger('qty');
            $table->unsignedInteger('qty_sold')->default(0);

            // Pricing
            $table->decimal('sell_price', 15, 4);
            $table->decimal('premium_tax', 15, 4)->default(0);
            $table->decimal('setup_fee', 15, 4)->default(0);

            $table->decimal('total_rev', 15, 4)->default(0);
            $table->decimal('avg_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);

            $table->decimal('profit', 15, 4)->default(0);

            // Status
            $table->enum('status', [
                'Pending',
                'Partial',
                'Complete',
            ])->default('Pending');

            // Automatically set when qty_sold >= qty
            $table->timestamp('completed_at')->nullable();

            // Listing timestamps (UTC)
            $table->date('listed_date');
            $table->time('listed_time');

            // Sale timestamps (UTC)
            $table->date('sold_date')->nullable();
            $table->time('sold_time')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};