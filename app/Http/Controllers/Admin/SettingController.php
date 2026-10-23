<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingRequest;
use App\Models\Setting;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => [
                'store_name' => Setting::get('store_name', 'Toko'),
                'store_address' => Setting::get('store_address', ''),
                'store_phone' => Setting::get('store_phone', ''),
                'admin_whatsapp' => Setting::get('admin_whatsapp', ''),
                'default_margin' => Setting::get('default_margin', 30),
                'price_rounding' => Setting::get('price_rounding', 100),
                'default_min_stock' => Setting::get('default_min_stock', 5),
                'online_warehouse_id' => Setting::get('online_warehouse_id'),
                'pos_warehouse_id' => Setting::get('pos_warehouse_id'),
                'order_expiry_hours' => Setting::get('order_expiry_hours', 24),
                'qris_image_path' => Setting::get('qris_image_path'),
                'qris_note' => Setting::get('qris_note', ''),
            ],
            'warehouses' => Warehouse::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $removeQris = (bool) ($data['remove_qris_image'] ?? false);
        unset($data['qris_image'], $data['remove_qris_image']);

        $currentQris = Setting::get('qris_image_path');

        if ($request->hasFile('qris_image')) {
            if ($currentQris) {
                Storage::disk('public')->delete($currentQris);
            }

            $data['qris_image_path'] = $request->file('qris_image')->store('settings', 'public');
        } elseif ($removeQris && $currentQris) {
            Storage::disk('public')->delete($currentQris);
            $data['qris_image_path'] = null;
        }

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('admin.settings.edit')->with('status', 'Pengaturan berhasil disimpan.');
    }
}
