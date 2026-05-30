<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buy_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Identifiers
            $table->string('order_id')->unique();

            // Item info
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('item_name');
            $table->string('tier', 10);
            $table->string('enchantment', 64)->nullable();
            $table->string('city', 100)->nullable();

            // Quantities
            $table->unsignedInteger('qty_ordered');
            $table->unsignedInteger('qty_received')->default(0);
            $table->unsignedInteger('qty_synced')->default(0);

            // Costs
            $table->decimal('cost_per', 15, 4);
            $table->decimal('setup_fee', 15, 4)->default(0);
            $table->decimal('total_setup', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->decimal('final_landed', 15, 4)->default(0);

            // Status
            $table->enum('status', ['Pending', 'Partial', 'Received', 'Sold Out'])->default('Pending');

            // Order timestamps — ordered_at is set by the app on create
            $table->timestamp('ordered_at')->nullable();
            $table->date('ordered_date')->nullable();       // user-facing date field
            $table->string('ordered_time', 8)->nullable();  // user-facing time e.g. "14:30"
            $table->string('received_time', 8)->nullable(); // time when fully received e.g. "16:45:00"
            $table->timestamp('completed_at')->nullable();  // set when qty_received = qty_ordered

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buy_orders');
    }
};