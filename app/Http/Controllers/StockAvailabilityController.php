<?php

namespace App\Http\Controllers;

use App\Models\StockLevel;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockAvailabilityController extends Controller
{
    public function __invoke(Request $request, CartService $cartService): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['nullable', 'string', 'max:2000'],
        ]);

        $productIds = collect(explode(',', (string) ($validated['ids'] ?? '')))
            ->map(fn (string $id) => filter_var(trim($id), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]))
            ->filter()
            ->unique()
            ->take(100)
            ->values();

        $warehouse = $cartService->warehouse();

        $levels = $warehouse && $productIds->isNotEmpty()
            ? StockLevel::query()
                ->where('warehouse_id', $warehouse->getKey())
                ->whereIn('product_id', $productIds)
                ->get()
                ->keyBy('product_id')
            : collect();

        return response()
            ->json([
                'warehouse_id' => $warehouse?->getKey(),
                'version' => (int) floor(microtime(true) * 1_000_000),
                'data' => $productIds->map(fn (int $productId) => [
                    'product_id' => $productId,
                    'available' => $levels->get($productId)?->availableQty() ?? 0.0,
                ])->all(),
            ])
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
