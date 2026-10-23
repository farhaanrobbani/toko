<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_confirm_payment_and_stock_is_committed(): void
    {
        $owner = User::factory()->owner()->create();
        $order = $this->pendingOrder();
        $level = StockLevel::query()->firstOrFail();

        $this->actingAs($owner)
            ->post(route('admin.orders.confirm', $order))
            ->assertRedirect();

        $order->refresh();
        $level->refresh();

        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->confirmed_at);
        $this->assertNotNull($order->stock_committed_at);
        $this->assertEquals(8, (float) $level->qty);
        $this->assertEquals(0, (float) $level->reserved_qty);
        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'type' => StockMovementType::SaleOnline->value,
            'quantity' => -2,
        ]);
    }

    public function test_confirming_payment_twice_does_not_deduct_stock_again(): void
    {
        $owner = User::factory()->owner()->create();
        $order = $this->pendingOrder();

        $this->actingAs($owner)->post(route('admin.orders.confirm', $order));
        $this->actingAs($owner)->post(route('admin.orders.confirm', $order))
            ->assertSessionHasErrors('status');

        $this->assertEquals(8, (float) StockLevel::query()->firstOrFail()->qty);
        $this->assertSame(1, $order->refresh()->items()->count());
        $this->assertSame(1, StockMovement::query()->where('type', StockMovementType::SaleOnline->value)->count());
    }

    public function test_cancelling_pending_order_releases_reserved_stock(): void
    {
        $owner = User::factory()->owner()->create();
        $order = $this->pendingOrder();

        $this->actingAs($owner)
            ->post(route('admin.orders.cancel', $order))
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertEquals(0, (float) StockLevel::query()->firstOrFail()->reserved_qty);
        $this->assertSame(0, StockMovement::query()->where('type', StockMovementType::SaleOnline->value)->count());
    }

    public function test_owner_can_set_shipping_cost_before_confirming_payment(): void
    {
        $owner = User::factory()->owner()->create();
        $order = $this->pendingOrder();

        $this->actingAs($owner)->post(route('admin.orders.shipping', $order), [
            'shipping_cost' => 5000,
        ])->assertRedirect();

        $this->assertEquals(5000, (float) $order->refresh()->shipping_cost);
        $this->assertEquals(21000, (float) $order->refresh()->total);

        $this->actingAs($owner)->post(route('admin.orders.confirm', $order));

        $this->actingAs($owner)->post(route('admin.orders.shipping', $order), [
            'shipping_cost' => 7000,
        ])->assertSessionHasErrors('status');
    }

    public function test_order_status_can_advance_to_completed_but_not_skip_steps(): void
    {
        $owner = User::factory()->owner()->create();
        $order = $this->pendingOrder();

        $this->actingAs($owner)->post(route('admin.orders.confirm', $order));

        $this->actingAs($owner)->post(route('admin.orders.advance', $order), [
            'status' => OrderStatus::Completed->value,
        ])->assertSessionHasErrors('status');

        foreach ([OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Completed] as $status) {
            $this->actingAs($owner)->post(route('admin.orders.advance', $order), [
                'status' => $status->value,
            ])->assertRedirect();
        }

        $this->assertSame(OrderStatus::Completed, $order->refresh()->status);
    }

    public function test_cashier_cannot_manage_online_orders(): void
    {
        $cashier = User::factory()->cashier()->create();
        $order = $this->pendingOrder();

        $this->actingAs($cashier)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('admin.orders.show', $order))->assertForbidden();
        $this->actingAs($cashier)->post(route('admin.orders.confirm', $order))->assertForbidden();
    }

    public function test_expired_pending_orders_are_cancelled_and_stock_released(): void
    {
        $this->pendingOrder();
        $order = Order::query()->firstOrFail();
        $order->update(['expires_at' => now()->subHour()]);

        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertEquals(0, (float) StockLevel::query()->firstOrFail()->reserved_qty);
    }

    private function pendingOrder(): Order
    {
        $warehouse = Warehouse::factory()->create(['is_default' => true]);
        $product = Product::factory()->create(['cost_price' => 5000, 'sell_price' => 8000]);
        Setting::set('online_warehouse_id', $warehouse->id);

        app(StockService::class)->record($product, $warehouse, StockMovementType::Adjustment, 10);

        $this->post(route('cart.store', $product), ['quantity' => 2]);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Pelanggan Uji',
            'customer_phone' => '081234567890',
            'customer_address' => 'Jl. Uji No. 1',
            'delivery_method' => 'delivery',
            'payment_method' => 'qris',
            'agree' => '1',
        ]);

        return Order::query()->latest('id')->firstOrFail();
    }
}
