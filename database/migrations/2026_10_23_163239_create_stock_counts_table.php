<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('sku', 50);
            $table->decimal('system_qty', 14, 3);
            $table->decimal('counted_qty', 14, 3)->nullable();
            $table->decimal('difference', 14, 3)->nullable();
            $table->timestamps();

            $table->unique(['stock_count_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_count_items');
        Schema::dropIfExists('stock_counts');
    }
};
