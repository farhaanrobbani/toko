<?php

namespace Tests\Feature\Admin;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_inventory_pages_and_stock_card(): void
    {
        $owner = User::factory()->owner()->create();
        $warehouse = Warehouse::factory()->create(['is_default' => true]);
        $product = Product::factory()->create();

        app(StockService::class)->record($product, $warehouse, StockMovementType::Adjustment, 5);

        $this->actingAs($owner)->get(route('admin.inventory.index'))->assertOk();
        $this->actingAs($owner)->get(route('admin.inventory.show', $product))->assertOk();
        $this->actingAs($owner)->get(route('admin.inventory.index', ['low' => 1]))->assertOk();
    }

    public function test_cashier_cannot_access_inventory_or_purchase_pages(): void
    {
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->create();

        $this->actingAs($cashier)->get(route('admin.inventory.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.inventory.show', $product))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.purchases.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.transfers.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.stock-counts.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.adjustments.create'))->assertForbidden();
    }

    public function test_adjustment_requires_reason_and_cannot_exceed_available_stock(): void
    {
        $owner = User::factory()->owner()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        app(StockService::class)->record($product, $warehouse, StockMovementType::Adjustment, 2);

        $this->actingAs($owner)->post(route('admin.adjustments.store'), [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'direction' => 'out',
            'quantity' => 1,
        ])->assertSessionHasErrors('note');

        $this->actingAs($owner)->post(route('admin.adjustments.store'), [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'direction' => 'out',
            'quantity' => 5,
            'note' => 'Barang rusak',
        ])->assertSessionHasErrors('quantity');

        $this->assertEquals(2, (float) StockLevel::query()->first()->qty);
    }
}
