<?php

namespace Tests\Feature\Admin;

use App\Enums\StockCountStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockLevel;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockCountService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_stock_count_creates_opname_difference_movement(): void
    {
        $owner = User::factory()->owner()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create(['cost_price' => 4000]);

        app(StockService::class)->record($product, $warehouse, StockMovementType::Adjustment, 10, 4000);

        $count = app(StockCountService::class)->create($warehouse, 'Opname uji', $owner);
        $item = $count->items()->where('product_id', $product->id)->firstOrFail();

        app(StockCountService::class)->saveCounts($count, [$item->id => 7]);
        app(StockCountService::class)->complete($count->refresh(), $owner);

        $this->assertSame(StockCountStatus::Completed, $count->refresh()->status);
        $this->assertEquals(7, (float) StockLevel::query()->first()->qty);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::Opname->value,
            'quantity' => -3,
        ]);
    }

    public function test_stock_count_cannot_complete_before_all_items_are_counted(): void
    {
        $owner = User::factory()->owner()->create();
        $warehouse = Warehouse::factory()->create();
        Product::factory()->count(2)->create();

        $count = app(StockCountService::class)->create($warehouse, null, $owner);
        $item = $count->items()->firstOrFail();

        app(StockCountService::class)->saveCounts($count, [$item->id => 1]);

        $this->actingAs($owner)->post(route('admin.stock-counts.complete', $count))
            ->assertSessionHasErrors('counted');

        $this->assertSame(StockCountStatus::Draft, $count->refresh()->status);
        $this->assertSame(0, StockCount::query()->where('status', StockCountStatus::Completed->value)->count());
    }
}
