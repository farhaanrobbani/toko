<?php

namespace Tests\Feature\Admin;

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PosPaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_summarizes_pos_and_online_sales_and_excludes_void_transactions(): void
    {
        [$warehouse, $product, $owner] = $this->fixtures();

        $this->travelTo(Carbon::parse('2026-10-10 10:00:00'));
        $this->createSale($warehouse, $product, $owner, 'POS-TEST-1', 15000);
        $this->createSale($warehouse, $product, $owner, 'POS-TEST-VOID', 15000, SaleStatus::Void);

        $this->travelTo(Carbon::parse('2026-10-11 11:00:00'));
        $this->createOrder($warehouse, $product);

        $this->travelTo(Carbon::parse('2026-09-30 09:00:00'));
        $this->createSale($warehouse, $product, $owner, 'POS-OUTSIDE', 15000);

        $this->travelBack();

        $from = Carbon::parse('2026-10-01')->startOfDay();
        $to = Carbon::parse('2026-10-31')->endOfDay();
        $report = app(ReportService::class)->build($from, $to);

        $this->assertSame(2, $report['summary']['transactions']);
        $this->assertSame(1, $report['summary']['pos_transactions']);
        $this->assertSame(1, $report['summary']['online_transactions']);
        $this->assertEquals(31000, $report['summary']['revenue']);
        $this->assertEquals(4000, $report['summary']['shipping']);
        $this->assertEquals(35000, $report['summary']['total_collected']);
        $this->assertEquals(1000, $report['summary']['discount']);
        $this->assertEquals(20000, $report['summary']['cogs']);
        $this->assertEquals(11000, $report['summary']['gross_profit']);
        $this->assertEquals(15500, $report['summary']['average']);
        $this->assertSame(2, $report['recent']->count());
        $this->assertSame(4.0, $report['products']->first()['quantity']);
        $this->assertEquals(32000, $report['products']->first()['revenue']);

        $this->actingAs($owner)
            ->get(route('admin.reports.index', [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertSee('Laporan Penjualan')
            ->assertSee('Rp 35.000')
            ->assertSee('Rp 11.000')
            ->assertSee('POS-TEST-1')
            ->assertDontSee('POS-TEST-VOID')
            ->assertDontSee('POS-OUTSIDE');
    }

    public function test_report_respects_date_filters(): void
    {
        [$warehouse, $product, $owner] = $this->fixtures();

        $this->travelTo(Carbon::parse('2026-10-05 10:00:00'));
        $today = $this->createSale($warehouse, $product, $owner, 'POS-IN', 15000);

        $this->travelTo(Carbon::parse('2026-10-20 10:00:00'));
        $this->createSale($warehouse, $product, $owner, 'POS-OUT', 15000);

        $this->travelBack();

        $this->actingAs($owner)->get(route('admin.reports.index', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
        ]))->assertOk()->assertSee($today->sale_number)->assertDontSee('POS-OUT');

        $this->actingAs($owner)->get(route('admin.reports.index', [
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-01',
        ]))->assertSessionHasErrors('end_date');
    }

    public function test_owner_can_export_report_as_csv(): void
    {
        [$warehouse, $product, $owner] = $this->fixtures();

        $this->travelTo(Carbon::parse('2026-10-10 10:00:00'));
        $this->createSale($warehouse, $product, $owner, 'POS-TEST-1', 15000);
        $this->createOrder($warehouse, $product);

        $this->travelBack();

        $response = $this->actingAs($owner)->get(route('admin.reports.export', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('POS-TEST-1', $csv);
        $this->assertStringContainsString('ORD-TEST-1', $csv);
        $this->assertStringContainsString('Laba Kotor', $csv);
    }

    public function test_cashier_cannot_access_reports_or_export(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.reports.export'))->assertForbidden();
    }

    private function fixtures(): array
    {
        $owner = User::factory()->owner()->create();
        $warehouse = Warehouse::factory()->create(['name' => 'Toko', 'code' => 'toko']);
        $product = Product::factory()->create([
            'name' => 'Air Mineral',
            'sku' => 'MNM-001',
            'cost_price' => 5000,
            'sell_price' => 8000,
        ]);

        return [$warehouse, $product, $owner];
    }

    private function createSale(Warehouse $warehouse, Product $product, User $cashier, string $number, float $total, SaleStatus $status = SaleStatus::Completed): Sale
    {
        $sale = Sale::query()->create([
            'sale_number' => $number,
            'status' => $status,
            'payment_method' => PosPaymentMethod::Cash,
            'warehouse_id' => $warehouse->id,
            'cashier_id' => $cashier->id,
            'customer_name' => null,
            'subtotal' => 16000,
            'discount' => $total < 16000 ? 1000 : 0,
            'total' => $total,
            'paid' => $total,
            'change' => 0,
            'voided_at' => $status === SaleStatus::Void ? now() : null,
            'voided_by' => $status === SaleStatus::Void ? $cashier->id : null,
            'void_reason' => $status === SaleStatus::Void ? 'Uji void' : null,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit' => $product->unit,
            'price' => 8000,
            'quantity' => 2,
            'subtotal' => 16000,
            'cost_price' => 5000,
        ]);

        return $sale;
    }

    private function createOrder(Warehouse $warehouse, Product $product): Order
    {
        $order = Order::query()->create([
            'order_number' => 'ORD-TEST-1',
            'tracking_token' => str_repeat('x', 48),
            'status' => OrderStatus::Paid,
            'payment_method' => PaymentMethod::Qris,
            'delivery_method' => DeliveryMethod::Delivery,
            'customer_name' => 'Pelanggan Online',
            'customer_phone' => '081234567890',
            'customer_address' => 'Jl. Uji No. 1',
            'warehouse_id' => $warehouse->id,
            'subtotal' => 16000,
            'shipping_cost' => 4000,
            'total' => 20000,
            'confirmed_at' => now(),
            'stock_committed_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit' => $product->unit,
            'price' => 8000,
            'quantity' => 2,
            'subtotal' => 16000,
            'cost_price' => 5000,
        ]);

        return $order;
    }
}
