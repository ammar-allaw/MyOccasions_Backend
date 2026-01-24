<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceProviderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('userId') ?? auth()->id();
        
        return [
            // User fields
            'phone_number' => 'nullable|string|unique:users,phone_number,' . $userId,
            'password' => 'nullable|string|min:8',            
            'name' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description' => 'nullable|string|nullable',
            'description_en' => 'nullable|string|nullable',
            'location' => 'nullable|string|max:255',
            'location_en' => 'nullable|string|max:255',
            'address_url' => 'nullable|string|url|nullable',
            'government_id' => 'nullable|exists:governments,id',
            'region_id' => 'nullable|exists:regions,id',
            
            'image' => 'nullable|array',
            'image.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'image_id' => 'nullable|array',
            'image_id.*' => 'nullable|exists:media,id',
            'replace_all' => 'nullable|boolean',
            'status_id' => 'nullable|exists:statuses,id',
            'rejection_reason' => 'nullable|string|required_if:status_id,2',
            
        ];
    }
}
