<?php

namespace Tests\Feature\Admin;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_adds_stock_and_recalculates_moving_average(): void
    {
        $owner = User::factory()->owner()->create();
        $warehouse = Warehouse::factory()->create();
        $supplier = Supplier::query()->create(['name' => 'Supplier Uji']);
        $product = Product::factory()->create(['cost_price' => 10000]);

        app(StockService::class)->record(
            $product,
            $warehouse,
            StockMovementType::Adjustment,
            10,
            10000,
        );

        $response = $this->actingAs($owner)->post(route('admin.purchases.store'), [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'purchased_at' => now()->toDateString(),
            'invoice_number' => 'INV-001',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 12000],
            ],
        ]);

        $response->assertRedirect();

        $product->refresh();

        $this->assertEquals(11000, (float) $product->cost_price);
        $this->assertEquals(20, (float) StockLevel::query()->first()->qty);
        $this->assertDatabaseHas('purchases', ['total' => 120000]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::Purchase->value,
            'quantity' => 10,
        ]);
    }

    public function test_cashier_cannot_record_purchase(): void
    {
        $cashier = User::factory()->cashier()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($cashier)->post(route('admin.purchases.store'), [
            'warehouse_id' => $warehouse->id,
            'purchased_at' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 1000],
            ],
        ])->assertForbidden();
    }
}
