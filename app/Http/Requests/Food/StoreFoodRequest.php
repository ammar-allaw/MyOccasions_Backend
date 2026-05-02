<?php

namespace App\Http\Requests\Food;

use Illuminate\Foundation\Http\FormRequest;

class StoreFoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'main_key_id' => 'required|exists:main_keys,id',
            'service_provider_id' => 'nullable|integer|exists:service_providers,id',
            'description' => 'nullable|string|min:10',
            'description_en' => 'nullable|string|min:10',
            'short_description' => 'nullable|string|max:255',
            'short_description_en' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lte:price',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'is_available' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'preparation_time' => 'nullable|integer|min:0',
            'calories' => 'nullable|integer|min:0',
            'ingredients' => 'nullable|string',
            'portion_size' => 'nullable|string|max:100',
        ];
    }
}
