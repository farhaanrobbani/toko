<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->numberBetween(2_000, 50_000);

        return [
            'category_id' => Category::factory(),
            'sku' => 'SKU-'.fake()->unique()->numerify('#####'),
            'barcode' => fake()->unique()->numerify('899##########'),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'unit' => 'pcs',
            'cost_price' => $cost,
            'sell_price' => round($cost * 1.3, -2),
            'min_stock' => 5,
            'margin_override' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
