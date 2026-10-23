<?php

namespace Tests\Feature\Pos;

use App\Enums\PosPaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_open_pos_and_see_products_at_selected_warehouse(): void
    {
        $cashier = User::factory()->cashier()->create();
        [$warehouse, $product] = $this->stockedProduct(5);

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee($warehouse->name)
            ->assertSee('Simpan Pembayaran')
            ->assertDontSee('Stok Opname');
    }

    public function test_cashier_can_complete_cash_sale_and_stock_is_deducted(): void
    {
        $cashier = User::factory()->cashier()->create();
        [$warehouse, $product] = $this->stockedProduct(5);

        $response = $this->actingAs($cashier)->post(route('pos.store'), [
            'warehouse_id' => $warehouse->id,
            'payment_method' => PosPaymentMethod::Cash->value,
            'items' => json_encode([['product_id' => $product->id, 'quantity' => 2]]),
            'discount' => 1000,
            'paid' => 15000,
        ]);

        $sale = Sale::query()->firstOrFail();

        $response->assertRedirect(route('pos.receipt', $sale));

        $this->assertSame(SaleStatus::Completed, $sale->status);
        $this->assertSame(PosPaymentMethod::Cash, $sale->payment_method);
        $this->assertEquals(16000, (float) $sale->subtotal);
        $this->assertEquals(1000, (float) $sale->discount);
        $this->assertEquals(15000, (float) $sale->total);
        $this->assertEquals(15000, (float) $sale->paid);
        $this->assertEquals(0, (float) $sale->change);
        $this->assertSame($cashier->id, $sale->cashier_id);
        $this->assertSame($warehouse->id, $sale->warehouse_id);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 16000,
        ]);
        $this->assertEquals(3, (float) StockLevel::query()->firstOrFail()->qty);
        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'type' => StockMovementType::SalePos->value,
            'quantity' => -2,
        ]);

        $this->actingAs($cashier)
            ->get(route('pos.receipt', $sale))
            ->assertOk()
            ->assertSee($sale->sale_number)
            ->assertSee('Air Mineral');
    }

    public function test_sale_is_rejected_when_stock_is_insufficient(): void
    {
        $cashier = User::factory()->cashier()->create();
        [$warehouse, $product] = $this->stockedProduct(1);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'warehouse_id' => $warehouse->id,
            'payment_method' => PosPaymentMethod::Cash->value,
            'items' => json_encode([['product_id' => $product->id, 'quantity' => 2]]),
            'paid' => 20000,
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(0, Sale::query()->count());
        $this->assertEquals(1, (float) StockLevel::query()->firstOrFail()->qty);
        $this->assertSame(0, StockMovement::query()->where('type', StockMovementType::SalePos->value)->count());
    }

    public function test_sale_requires_sufficient_payment_and_valid_discount(): void
    {
        $cashier = User::factory()->cashier()->create();
        [$warehouse, $product] = $this->stockedProduct(5);

        $payload = [
            'warehouse_id' => $warehouse->id,
            'payment_method' => PosPaymentMethod::Cash->value,
            'items' => json_encode([['product_id' => $product->id, 'quantity' => 1]]),
        ];

        $this->actingAs($cashier)->post(route('pos.store'), $payload + ['paid' => 1000])
            ->assertSessionHasErrors('paid');

        $this->actingAs($cashier)->post(route('pos.store'), $payload + ['paid' => 20000, 'discount' => 99999])
            ->assertSessionHasErrors('discount');

        $this->assertSame(0, Sale::query()->count());
        $this->assertEquals(5, (float) StockLevel::query()->firstOrFail()->qty);
    }

    public function test_sale_only_deducts_stock_from_the_selected_warehouse(): void
    {
        $cashier = User::factory()->cashier()->create();
        $main = Warehouse::factory()->create(['is_default' => true]);
        $store = Warehouse::factory()->create(['code' => 'toko']);
        $product = Product::factory()->create(['sell_price' => 8000, 'cost_price' => 5000]);
        $stock = app(StockService::class);

        $stock->record($product, $main, StockMovementType::Adjustment, 10);
        $stock->record($product, $store, StockMovementType::Adjustment, 5);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'warehouse_id' => $main->id,
            'payment_method' => PosPaymentMethod::Qris->value,
            'items' => json_encode([['product_id' => $product->id, 'quantity' => 2]]),
            'paid' => 16000,
        ])->assertRedirect();

        $this->assertEquals(8, (float) StockLevel::query()->where('warehouse_id', $main->id)->value('qty'));
        $this->assertEquals(5, (float) StockLevel::query()->where('warehouse_id', $store->id)->value('qty'));
    }

    public function test_owner_can_void_sale_and_restore_stock(): void
    {
        $owner = User::factory()->owner()->create();
        $cashier = User::factory()->cashier()->create();
        [$warehouse, $product] = $this->stockedProduct(5);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'warehouse_id' => $warehouse->id,
            'payment_method' => PosPaymentMethod::Cash->value,
            'items' => json_encode([['product_id' => $product->id, 'quantity' => 3]]),
            'paid' => 24000,
        ]);

        $sale = Sale::query()->firstOrFail();
        $this->assertEquals(2, (float) StockLevel::query()->firstOrFail()->qty);

        $this->actingAs($owner)
            ->post(route('pos.void', $sale), ['void_reason' => 'Salah input'])
            ->assertRedirect(route('pos.receipt', $sale));

        $sale->refresh();

        $this->assertSame(SaleStatus::Void, $sale->status);
        $this->assertSame($owner->id, $sale->voided_by);
        $this->assertSame('Salah input', $sale->void_reason);
        $this->assertEquals(5, (float) StockLevel::query()->firstOrFail()->qty);
        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'type' => StockMovementType::Return->value,
            'quantity' => 3,
        ]);

        $this->actingAs($owner)
            ->post(route('pos.void', $sale), ['void_reason' => 'Lagi'])
            ->assertSessionHasErrors('status');

        $this->assertEquals(5, (float) StockLevel::query()->firstOrFail()->qty);
    }

    public function test_cashier_cannot_void_sale(): void
    {
        $cashier = User::factory()->cashier()->create();
        [$warehouse, $product] = $this->stockedProduct(5);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'warehouse_id' => $warehouse->id,
            'payment_method' => PosPaymentMethod::Cash->value,
            'items' => json_encode([['product_id' => $product->id, 'quantity' => 1]]),
            'paid' => 8000,
        ]);

        $sale = Sale::query()->firstOrFail();

        $this->actingAs($cashier)
            ->post(route('pos.void', $sale), ['void_reason' => 'Coba'])
            ->assertForbidden();

        $this->assertSame(SaleStatus::Completed, $sale->refresh()->status);
    }

    private function stockedProduct(float $quantity): array
    {
        $warehouse = Warehouse::factory()->create(['name' => 'Toko', 'code' => 'toko', 'is_default' => false]);
        $product = Product::factory()->create([
            'name' => 'Air Mineral',
            'sku' => 'MNM-001',
            'barcode' => '8990000000011',
            'unit' => 'botol',
            'cost_price' => 5000,
            'sell_price' => 8000,
        ]);

        Setting::set('pos_warehouse_id', $warehouse->id);

        app(StockService::class)->record($product, $warehouse, StockMovementType::Adjustment, $quantity);

        return [$warehouse, $product];
    }
}
