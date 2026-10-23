<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_product_with_generated_slug(): void
    {
        $owner = User::factory()->owner()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($owner)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'sku' => 'SKU-UJI-01',
            'barcode' => '8999999999999',
            'name' => 'Produk Uji Coba',
            'description' => 'Deskripsi singkat.',
            'unit' => 'pcs',
            'cost_price' => 10000,
            'sell_price' => 15000,
            'min_stock' => 3,
            'margin_override' => null,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU-UJI-01',
            'slug' => 'produk-uji-coba',
            'category_id' => $category->id,
        ]);
    }

    public function test_owner_can_view_product_forms(): void
    {
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create();

        $this->actingAs($owner)->get(route('admin.products.create'))->assertOk();
        $this->actingAs($owner)->get(route('admin.products.edit', $product))->assertOk();
    }

    public function test_cashier_cannot_create_product(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->post(route('admin.products.store'), [
            'sku' => 'SKU-UJI-02',
            'name' => 'Produk Terlarang',
            'unit' => 'pcs',
            'cost_price' => 1000,
            'sell_price' => 2000,
            'min_stock' => 1,
        ])->assertForbidden();

        $this->assertDatabaseMissing('products', ['sku' => 'SKU-UJI-02']);
    }

    public function test_product_requires_unique_sku(): void
    {
        $owner = User::factory()->owner()->create();
        Product::factory()->create(['sku' => 'SKU-DUPLIKAT']);

        $this->actingAs($owner)->post(route('admin.products.store'), [
            'sku' => 'SKU-DUPLIKAT',
            'name' => 'Produk Lain',
            'unit' => 'pcs',
            'cost_price' => 1000,
            'sell_price' => 2000,
            'min_stock' => 1,
        ])->assertSessionHasErrors('sku');
    }
}
