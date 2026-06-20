<?php

namespace App\Http\Requests\ServiceProvider;

use App\Http\Requests\Concerns\NormalizesSyrianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceProviderRequest extends FormRequest
{
    use NormalizesSyrianPhoneNumber;

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
    protected function prepareForValidation(): void
    {
        $this->mergeNormalizedSyrianPhoneNumber();

        if ($this->has('type_id') && !is_array($this->type_id)) {
            $this->merge(['type_id' => [$this->type_id]]);
        }

        if ($this->has('delete_type_id') && !is_array($this->delete_type_id)) {
            $this->merge(['delete_type_id' => [$this->delete_type_id]]);
        }
    }

    public function rules(): array
    {
        $userId = $this->route('userId') ?? auth()->id();
        
        return [
            // User fields
            'phone_number' => [
                'nullable',
                'string',
                $this->syrianPhoneNumberRule(),
                Rule::unique('users', 'phone_number')
                    ->ignore($userId)
                    ->where(function ($query) {
                        return $query->where('role_id', $this->input('role_id'));
                    }),
            ],
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
            'cover_image' => 'nullable|array',
            'cover_image.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover_image_id' => 'nullable|array',
            'cover_image_id.*' => 'nullable|exists:media,id',
            'replace_all_cover' => 'nullable|boolean',
            'youtube_link' => 'nullable|url',
            'youtube_media_id' => 'nullable|integer|exists:media,id',
            'status_id' => 'nullable|exists:statuses,id',
            'rejection_reason' => 'nullable|string|required_if:status_id,2',
            'type_id' => 'nullable|array',
            'type_id.*' => 'integer|exists:types,id',
            'delete_type_id' => 'nullable|array',
            'delete_type_id.*' => 'integer|exists:types,id',
        ];
    }
}
