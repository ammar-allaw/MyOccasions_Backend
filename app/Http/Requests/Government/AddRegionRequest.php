<?php

namespace App\Http\Requests\Government;

use Illuminate\Foundation\Http\FormRequest;

class AddRegionRequest extends FormRequest
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
            'name'=>'required|string|min:3|max:200|unique:regions,name,except,id',
            'name_en'=>'required|string|min:3|max:200|unique:regions,name_en,except,id',
            'government_id'=>'required|exists:governments,id',
        ];
    }
}
