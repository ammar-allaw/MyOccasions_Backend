<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class DeleteClientAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return $this->locale() === 'en'
            ? [
                'current_password.required' => 'Current password is required',
                'current_password.string' => 'Current password must be a string',
            ]
            : [
                'current_password.required' => 'كلمة المرور الحالية مطلوبة',
                'current_password.string' => 'كلمة المرور الحالية يجب أن تكون نصاً',
            ];
    }

    private function locale(): string
    {
        return str_starts_with(strtolower((string) $this->header('Accept-Language', 'ar')), 'en')
            ? 'en'
            : 'ar';
    }
}
