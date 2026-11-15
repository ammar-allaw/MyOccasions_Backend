<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetForgottenPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reset_token' => ['required', 'string', 'size:64'],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'max:50',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
            ],
        ];
    }

    public function messages(): array
    {
        $messages = [
            'ar' => [
                'reset_token.required' => 'رمز إعادة تعيين كلمة المرور مطلوب',
                'reset_token.string' => 'رمز إعادة تعيين كلمة المرور غير صالح',
                'reset_token.size' => 'رمز إعادة تعيين كلمة المرور غير صالح',
                'new_password.required' => 'كلمة المرور الجديدة مطلوبة',
                'new_password.string' => 'كلمة المرور الجديدة يجب أن تكون نصًا',
                'new_password.min' => 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل',
                'new_password.max' => 'كلمة المرور الجديدة يجب ألا تتجاوز 50 حرفًا',
                'new_password.confirmed' => 'تأكيد كلمة المرور الجديدة غير مطابق',
                'new_password.regex' => 'كلمة المرور يجب أن تحتوي على حرف إنجليزي كبير ورقم ورمز خاص على الأقل',
            ],
            'en' => [
                'reset_token.required' => 'Reset token is required',
                'reset_token.string' => 'Reset token is invalid',
                'reset_token.size' => 'Reset token is invalid',
                'new_password.required' => 'New password is required',
                'new_password.string' => 'New password must be a string',
                'new_password.min' => 'New password must be at least 8 characters',
                'new_password.max' => 'New password must not be greater than 50 characters',
                'new_password.confirmed' => 'New password confirmation does not match',
                'new_password.regex' => 'Password must contain at least one uppercase letter, one number, and one special character',
            ],
        ];

        return $messages[$this->locale()];
    }

    private function locale(): string
    {
        return str_starts_with(strtolower((string) $this->header('Accept-Language', 'ar')), 'en')
            ? 'en'
            : 'ar';
    }
}
