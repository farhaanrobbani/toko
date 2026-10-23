<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'category_id',
    'sku',
    'barcode',
    'name',
    'slug',
    'description',
    'unit',
    'cost_price',
    'sell_price',
    'min_stock',
    'margin_override',
    'image_path',
    'is_active',
])]
class Product extends Model
{
    use HasFactory, HasSlug;

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sell_price' => 'decimal:2',
            'margin_override' => 'decimal:2',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function effectiveMargin(float $defaultMargin): float
    {
        return $this->margin_override !== null
            ? (float) $this->margin_override
            : $defaultMargin;
    }

    public function marginPercentage(): ?float
    {
        $cost = (float) $this->cost_price;

        if ($cost <= 0) {
            return null;
        }

        return (((float) $this->sell_price - $cost) / $cost) * 100;
    }

    public function recommendedPrice(float $margin, int $rounding = 100): float
    {
        $base = round((float) $this->cost_price * (1 + ($margin / 100)), 2);

        if ($base <= 0) {
            return 0;
        }

        $rounding = max(1, $rounding);

        return ceil($base / $rounding) * $rounding;
    }
}
