<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMainKeyRequest extends FormRequest
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
            'role_id' => 'required|exists:roles,id',
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('main_keys')->ignore($this->route('id'))->where(function ($query) {
                    return $query->where('role_id', $this->role_id);
                })
            ],
            'key_en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('main_keys')->ignore($this->route('id'))->where(function ($query) {
                    return $query->where('role_id', $this->role_id);
                })
            ],
        ];
    }
}
