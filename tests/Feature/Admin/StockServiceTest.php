<?php

namespace Tests\Feature\Admin;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_movement_updates_stock_level_and_card(): void
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $service = app(StockService::class);

        $movement = $service->record(
            product: $product,
            warehouse: $warehouse,
            type: StockMovementType::Adjustment,
            quantity: 12.5,
            unitCost: 1000,
            note: 'Stok awal',
        );

        $this->assertSame(StockMovementType::Adjustment, $movement->type);
        $this->assertEquals(12.5, (float) StockLevel::query()->first()->qty);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 12.5,
        ]);
    }

    public function test_outbound_movement_cannot_make_stock_negative(): void
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $service = app(StockService::class);

        $service->record($product, $warehouse, StockMovementType::Adjustment, 2);

        try {
            $service->record($product, $warehouse, StockMovementType::TransferOut, -3);
            $this->fail('Outbound movement should have failed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }

        $this->assertEquals(2, (float) StockLevel::query()->first()->qty);
        $this->assertSame(1, StockMovement::query()->count());
    }
}
