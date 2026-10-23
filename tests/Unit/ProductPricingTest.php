<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductPricingTest extends TestCase
{
    public function test_recommended_price_applies_margin_and_rounding(): void
    {
        $product = new Product([
            'cost_price' => 10000,
            'sell_price' => 15000,
        ]);

        $this->assertSame(13000.0, $product->recommendedPrice(30, 100));

        $product->cost_price = 10100;

        $this->assertSame(13500.0, $product->recommendedPrice(30, 500));
    }

    public function test_margin_percentage_is_calculated_from_cost_and_sell_price(): void
    {
        $product = new Product([
            'cost_price' => 10000,
            'sell_price' => 15000,
        ]);

        $this->assertSame(50.0, $product->marginPercentage());
    }

    public function test_product_margin_override_takes_precedence(): void
    {
        $product = new Product([
            'cost_price' => 10000,
            'sell_price' => 15000,
            'margin_override' => 20,
        ]);

        $this->assertSame(20.0, $product->effectiveMargin(35));
        $this->assertSame(35.0, (new Product)->effectiveMargin(35));
    }

    public function test_margin_is_null_when_cost_is_zero(): void
    {
        $product = new Product([
            'cost_price' => 0,
            'sell_price' => 15000,
        ]);

        $this->assertNull($product->marginPercentage());
        $this->assertSame(0.0, $product->recommendedPrice(30));
    }
}
