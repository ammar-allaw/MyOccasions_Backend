<?php

namespace App\Http\Requests\Food;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateFoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'main_key_id' => 'nullable|exists:main_keys,id',
            'description' => 'nullable|string|min:10',
            'description_en' => 'nullable|string|min:10',
            'short_description' => 'nullable|string|max:255',
            'short_description_en' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'status_id' => 'nullable|exists:statuses,id',
            'rejection_reason' => 'nullable|string|required_if:status_id,2',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'is_available' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'preparation_time' => 'nullable|integer|min:0',
            'calories' => 'nullable|integer|min:0',
            'ingredients' => 'nullable|string',
            'portion_size' => 'nullable|string|max:100',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->filled('status_id') && ! Auth::guard('owner')->check()) {
                $validator->errors()->add('status_id', 'Only owner can update food status.');
            }
        });
    }
}
