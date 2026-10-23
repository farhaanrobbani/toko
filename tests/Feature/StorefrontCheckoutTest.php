<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_browse_products_and_view_product_detail(): void
    {
        $product = Product::factory()->create(['name' => 'Produk Toko']);

        $this->get(route('home'))->assertOk()->assertSee('Produk Toko');
        $this->get(route('store.products.show', $product))->assertOk()->assertSee('Produk Toko');
    }

    public function test_guest_can_add_to_cart_and_open_checkout(): void
    {
        [$warehouse, $product] = $this->stockedProduct(10);

        $this->post(route('cart.store', $product), ['quantity' => 2])
            ->assertRedirect();

        $this->assertSame([$product->id => 2.0], session('cart'));

        $this->get(route('cart.index'))->assertOk()->assertSee($product->name);
        $this->get(route('checkout.create'))->assertOk()->assertSee('Checkout');
    }

    public function test_checkout_creates_order_and_reserves_stock(): void
    {
        [$warehouse, $product] = $this->stockedProduct(10);

        $this->post(route('cart.store', $product), ['quantity' => 2]);

        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'Pelanggan Uji',
            'customer_phone' => '081234567890',
            'customer_address' => 'Jl. Uji No. 1',
            'delivery_method' => 'delivery',
            'payment_method' => 'qris',
            'agree' => '1',
        ]);

        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('orders.show', $order->tracking_token));

        $this->assertSame('pending_payment', $order->status->value);
        $this->assertEquals(16000, (float) $order->total);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $level = StockLevel::query()->firstOrFail();
        $this->assertEquals(10, (float) $level->qty);
        $this->assertEquals(2, (float) $level->reserved_qty);

        $this->assertNull(session('cart'));
    }

    public function test_cart_cannot_exceed_available_stock(): void
    {
        [$warehouse, $product] = $this->stockedProduct(2);

        $this->post(route('cart.store', $product), ['quantity' => 3])
            ->assertSessionHasErrors('quantity');

        $this->assertNull(session('cart'));
    }

    public function test_customer_can_track_order_with_token_or_phone(): void
    {
        [$warehouse, $product] = $this->stockedProduct(5);
        $order = $this->placeOrder($product);

        $this->get(route('orders.show', $order->tracking_token))->assertOk()->assertSee($order->order_number);

        $this->post(route('track.lookup'), [
            'order_number' => $order->order_number,
            'customer_phone' => '081234567890',
        ])->assertRedirect(route('orders.show', $order->tracking_token));

        $this->post(route('track.lookup'), [
            'order_number' => $order->order_number,
            'customer_phone' => '089999999999',
        ])->assertSessionHasErrors('order_number');
    }

    public function test_guest_can_upload_payment_proof_while_waiting(): void
    {
        Storage::fake('public');

        [$warehouse, $product] = $this->stockedProduct(5);
        $order = $this->placeOrder($product);

        $this->post(route('orders.proof', $order->tracking_token), [
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();

        $order->refresh();

        $this->assertNotNull($order->payment_proof_path);
        $this->assertNotNull($order->payment_uploaded_at);
        Storage::disk('public')->assertExists($order->payment_proof_path);
    }

    private function stockedProduct(float $quantity): array
    {
        $warehouse = Warehouse::factory()->create(['name' => 'Gudang', 'is_default' => true]);
        $product = Product::factory()->create(['cost_price' => 5000, 'sell_price' => 8000, 'unit' => 'pcs']);

        Setting::set('online_warehouse_id', $warehouse->id);

        app(StockService::class)->record(
            $product,
            $warehouse,
            StockMovementType::Adjustment,
            $quantity,
        );

        return [$warehouse, $product];
    }

    private function placeOrder(Product $product): Order
    {
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
