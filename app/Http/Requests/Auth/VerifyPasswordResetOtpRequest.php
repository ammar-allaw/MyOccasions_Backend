<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizesSyrianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyPasswordResetOtpRequest extends FormRequest
{
    use NormalizesSyrianPhoneNumber;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeNormalizedSyrianPhoneNumber();

        if (! $this->filled('account_type')) {
            $this->merge(['account_type' => 'client']);
        }
    }

    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', $this->syrianPhoneNumberRule()],
            'account_type' => ['required', 'string', Rule::in(['client', 'service_provider'])],
            'code' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        $messages = [
            'ar' => [
                'phone_number.required' => 'رقم الهاتف مطلوب',
                'phone_number.string' => 'رقم الهاتف يجب أن يكون نصًا',
                'phone_number.regex' => 'صيغة رقم الهاتف غير صحيحة',
                'account_type.required' => 'نوع الحساب مطلوب',
                'account_type.string' => 'نوع الحساب يجب أن يكون نصًا',
                'account_type.in' => 'نوع الحساب يجب أن يكون client أو service_provider',
                'code.required' => 'رمز التحقق مطلوب',
                'code.digits' => 'رمز التحقق يجب أن يكون 6 أرقام',
            ],
            'en' => [
                'phone_number.required' => 'Phone number is required',
                'phone_number.string' => 'Phone number must be a string',
                'phone_number.regex' => 'Phone number format is invalid',
                'account_type.required' => 'Account type is required',
                'account_type.string' => 'Account type must be a string',
                'account_type.in' => 'Account type must be client or service_provider',
                'code.required' => 'OTP code is required',
                'code.digits' => 'OTP code must be 6 digits',
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
