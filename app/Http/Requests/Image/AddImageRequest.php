<?php

namespace App\Http\Requests\Image;

use Illuminate\Foundation\Http\FormRequest;

class AddImageRequest extends FormRequest
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
        return [
            'image' => 'nullable',
            'image.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image_id' => 'nullable',
            'image_id.*' => 'nullable|integer|exists:media,id',
            'replace_all' => 'nullable|boolean'
        ];
    }
}
