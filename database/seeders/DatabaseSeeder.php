<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@toko.test'],
            ['name' => 'Owner Toko', 'password' => 'password', 'role' => UserRole::Owner],
        );

        User::query()->updateOrCreate(
            ['email' => 'kasir@toko.test'],
            ['name' => 'Kasir Toko', 'password' => 'password', 'role' => UserRole::Cashier],
        );

        $warehouse = Warehouse::query()->firstOrCreate(
            ['code' => 'gudang'],
            ['name' => 'Gudang', 'is_default' => true, 'is_active' => true],
        );

        $store = Warehouse::query()->firstOrCreate(
            ['code' => 'toko'],
            ['name' => 'Toko', 'is_default' => false, 'is_active' => true],
        );

        Supplier::query()->firstOrCreate(
            ['name' => 'PT Sumber Makmur'],
            ['phone' => '081200000001', 'address' => 'Jl. Raya No. 1', 'notes' => 'Supplier demo'],
        );

        $categories = collect([
            'Minuman',
            'Makanan Ringan',
            'Sembako',
            'Perlengkapan',
        ])->mapWithKeys(fn (string $name) => [
            $name => Category::query()->firstOrCreate(['name' => $name]),
        ]);

        $products = [
            ['sku' => 'MNM-001', 'barcode' => '8990000000011', 'name' => 'Air Mineral 600 ml', 'category' => 'Minuman', 'unit' => 'botol', 'cost' => 2500, 'sell' => 4000, 'min' => 24],
            ['sku' => 'MNM-002', 'barcode' => '8990000000028', 'name' => 'Teh Botol 350 ml', 'category' => 'Minuman', 'unit' => 'botol', 'cost' => 3500, 'sell' => 5000, 'min' => 12],
            ['sku' => 'MKR-001', 'barcode' => '8990000000035', 'name' => 'Keripik Kentang 68 g', 'category' => 'Makanan Ringan', 'unit' => 'bungkus', 'cost' => 8000, 'sell' => 12000, 'min' => 10],
            ['sku' => 'MKR-002', 'barcode' => '8990000000042', 'name' => 'Biskuit Cokelat 120 g', 'category' => 'Makanan Ringan', 'unit' => 'bungkus', 'cost' => 9500, 'sell' => 14000, 'min' => 10],
            ['sku' => 'SMB-001', 'barcode' => '8990000000059', 'name' => 'Beras Premium 5 kg', 'category' => 'Sembako', 'unit' => 'karung', 'cost' => 62000, 'sell' => 72000, 'min' => 5],
            ['sku' => 'SMB-002', 'barcode' => '8990000000066', 'name' => 'Minyak Goreng 1 L', 'category' => 'Sembako', 'unit' => 'botol', 'cost' => 15000, 'sell' => 18500, 'min' => 8],
            ['sku' => 'SMB-003', 'barcode' => '8990000000073', 'name' => 'Gula Pasir 1 kg', 'category' => 'Sembako', 'unit' => 'kg', 'cost' => 14000, 'sell' => 17000, 'min' => 8],
            ['sku' => 'PLK-001', 'barcode' => '8990000000080', 'name' => 'Sabun Mandi 85 g', 'category' => 'Perlengkapan', 'unit' => 'pcs', 'cost' => 3500, 'sell' => 5500, 'min' => 12],
        ];

        $stockService = app(StockService::class);

        foreach ($products as $data) {
            $product = Product::query()->firstOrCreate(
                ['sku' => $data['sku']],
                [
                    'barcode' => $data['barcode'],
                    'name' => $data['name'],
                    'category_id' => $categories[$data['category']]->id,
                    'unit' => $data['unit'],
                    'cost_price' => $data['cost'],
                    'sell_price' => $data['sell'],
                    'min_stock' => $data['min'],
                    'is_active' => true,
                ],
            );

            if (StockMovement::query()->where('product_id', $product->getKey())->exists()) {
                continue;
            }

            foreach ([[$warehouse, 40], [$store, 8]] as [$location, $quantity]) {
                $stockService->record(
                    product: $product,
                    warehouse: $location,
                    type: StockMovementType::Adjustment,
                    quantity: $quantity,
                    unitCost: (float) $product->cost_price,
                    note: 'Stok awal demo',
                    user: $owner,
                );
            }
        }

        $settings = [
            'store_name' => 'Toko',
            'store_address' => '',
            'store_phone' => '',
            'admin_whatsapp' => '',
            'default_margin' => 30,
            'price_rounding' => 100,
            'default_min_stock' => 5,
            'online_warehouse_id' => $warehouse->id,
            'order_expiry_hours' => 24,
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }

        $this->command?->info('Akun demo: owner@toko.test / kasir@toko.test (password: password)');
        $this->command?->info("Owner ID: {$owner->id}");
    }
}
