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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('invoice_number')->nullable();
            $table->date('purchased_at');
            $table->decimal('total', 14, 2)->default(0);
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['purchased_at', 'warehouse_id']);
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('sku', 50);
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 14, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
