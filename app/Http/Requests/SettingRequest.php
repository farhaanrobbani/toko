<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isOwner() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:100'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'store_phone' => ['nullable', 'string', 'max:30'],
            'admin_whatsapp' => ['nullable', 'string', 'max:30'],
            'default_margin' => ['required', 'numeric', 'min:0', 'max:1000'],
            'price_rounding' => ['required', 'integer', 'in:1,100,500,1000'],
            'default_min_stock' => ['required', 'integer', 'min:0'],
            'online_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'order_expiry_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ];
    }
}
