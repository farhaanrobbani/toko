<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Events\StockLevelUpdated;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RealtimeStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_changes_broadcast_available_quantity(): void
    {
        Event::fake([StockLevelUpdated::class]);

        ['warehouse' => $warehouse, 'product' => $product] = $this->stockedProduct(10);

        Event::assertDispatched(StockLevelUpdated::class, fn (StockLevelUpdated $event) => $event->productId === $product->id
            && $event->warehouseId === $warehouse->id
            && $event->available === 10.0
            && $event->broadcastAs() === 'stock.updated'
            && $event->broadcastOn()->name === 'stock.'.$warehouse->id);

        app(StockService::class)->reserve($product, $warehouse, 3);

        Event::assertDispatched(StockLevelUpdated::class, fn (StockLevelUpdated $event) => $event->available === 7.0);
    }

    public function test_failed_stock_change_does_not_broadcast(): void
    {
        ['warehouse' => $warehouse, 'product' => $product] = $this->stockedProduct(1);

        Event::fake([StockLevelUpdated::class]);

        try {
            app(StockService::class)->reserve($product, $warehouse, 2);
        } catch (ValidationException) {
            //
        }

        Event::assertNotDispatched(StockLevelUpdated::class);
    }

    public function test_stock_endpoint_returns_available_stock_for_online_warehouse(): void
    {
        ['warehouse' => $warehouse, 'product' => $product] = $this->stockedProduct(10);

        app(StockService::class)->reserve($product, $warehouse, 3);

        $otherWarehouse = Warehouse::factory()->create(['is_default' => false]);
        StockLevel::query()->create([
            'warehouse_id' => $otherWarehouse->id,
            'product_id' => $product->id,
            'qty' => 50,
            'reserved_qty' => 0,
        ]);

        $missingProduct = Product::factory()->create();

        $response = $this->getJson(route('stock.live', [
            'ids' => $product->id.','.$missingProduct->id.','.$product->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('warehouse_id', $warehouse->id);

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $data = collect($response->json('data'))->keyBy('product_id');

        $this->assertSame(7.0, (float) $data[$product->id]['available']);
        $this->assertSame(0.0, (float) $data[$missingProduct->id]['available']);
        $this->assertCount(2, $data);
    }

    public function test_stock_endpoint_rejects_invalid_ids_and_limits_payload(): void
    {
        $this->stockedProduct(5);

        $this->getJson(route('stock.live', ['ids' => 'abc,-1,0,2.5']))
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_storefront_pages_include_realtime_stock_client(): void
    {
        ['product' => $product] = $this->stockedProduct(5);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('name="stock-warehouse-id"', false)
            ->assertSee('name="stock-endpoint"', false)
            ->assertSee('stockCard(', false);

        $this->get(route('store.products.show', $product))
            ->assertOk()
            ->assertSee('productStock(', false);

        $this->post(route('cart.store', $product), ['quantity' => 2]);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('cartStock(', false);

        $this->get(route('checkout.create'))
            ->assertOk()
            ->assertSee('checkoutStock(', false);
    }

    /**
     * @return array{warehouse: Warehouse, product: Product}
     */
    private function stockedProduct(float $quantity): array
    {
        $warehouse = Warehouse::factory()->create(['is_default' => true]);
        $product = Product::factory()->create(['cost_price' => 5000, 'sell_price' => 8000, 'unit' => 'pcs']);

        Setting::set('online_warehouse_id', $warehouse->id);

        app(StockService::class)->record(
            $product,
            $warehouse,
            StockMovementType::Adjustment,
            $quantity,
        );

        return ['warehouse' => $warehouse, 'product' => $product];
    }
}
