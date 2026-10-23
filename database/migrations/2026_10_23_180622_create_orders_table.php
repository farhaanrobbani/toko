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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('tracking_token', 64)->unique();
            $table->string('status', 30)->default('pending_payment')->index();
            $table->string('payment_method', 20);
            $table->string('delivery_method', 20)->default('delivery');
            $table->string('customer_name');
            $table->string('customer_phone', 30)->index();
            $table->text('customer_address')->nullable();
            $table->text('customer_note')->nullable();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('shipping_cost', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('payment_proof_path')->nullable();
            $table->timestamp('payment_uploaded_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('stock_committed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('sku', 50);
            $table->string('unit', 20);
            $table->decimal('price', 14, 2);
            $table->decimal('quantity', 14, 3);
            $table->decimal('subtotal', 14, 2);
            $table->decimal('cost_price', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
