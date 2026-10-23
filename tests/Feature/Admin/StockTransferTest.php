<?php

namespace Tests\Feature\Admin;

use App\Enums\StockMovementType;
use App\Enums\TransferStatus;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_moves_stock_when_shipped_and_received(): void
    {
        $owner = User::factory()->owner()->create();
        $from = Warehouse::factory()->create(['name' => 'Gudang']);
        $to = Warehouse::factory()->create(['name' => 'Toko']);
        $product = Product::factory()->create(['cost_price' => 5000]);

        app(StockService::class)->record($product, $from, StockMovementType::Adjustment, 10);

        $this->actingAs($owner)->post(route('admin.transfers.store'), [
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 4],
            ],
        ])->assertRedirect();

        $transfer = StockTransfer::query()->firstOrFail();
        $this->assertSame(TransferStatus::Draft, $transfer->status);

        $this->actingAs($owner)->post(route('admin.transfers.ship', $transfer))->assertRedirect();
        $this->assertSame(TransferStatus::InTransit, $transfer->refresh()->status);
        $this->assertEquals(6, $this->level($product, $from));
        $this->assertEquals(0, $this->level($product, $to));

        $this->actingAs($owner)->post(route('admin.transfers.receive', $transfer))->assertRedirect();
        $this->assertSame(TransferStatus::Received, $transfer->refresh()->status);
        $this->assertEquals(6, $this->level($product, $from));
        $this->assertEquals(4, $this->level($product, $to));

        $this->actingAs($owner)->post(route('admin.transfers.receive', $transfer))
            ->assertSessionHasErrors('status');

        $this->assertEquals(4, $this->level($product, $to));
    }

    public function test_transfer_cannot_be_shipped_with_insufficient_stock(): void
    {
        $owner = User::factory()->owner()->create();
        $from = Warehouse::factory()->create();
        $to = Warehouse::factory()->create();
        $product = Product::factory()->create();

        app(StockService::class)->record($product, $from, StockMovementType::Adjustment, 2);

        $this->actingAs($owner)->post(route('admin.transfers.store'), [
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ]);

        $transfer = StockTransfer::query()->firstOrFail();

        $this->actingAs($owner)->post(route('admin.transfers.ship', $transfer))
            ->assertSessionHasErrors('quantity');

        $this->assertSame(TransferStatus::Draft, $transfer->refresh()->status);
        $this->assertEquals(2, $this->level($product, $from));
    }

    private function level(Product $product, Warehouse $warehouse): float
    {
        return (float) StockLevel::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('qty');
    }
}
