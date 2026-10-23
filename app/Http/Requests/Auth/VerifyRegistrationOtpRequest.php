<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizesSyrianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class VerifyRegistrationOtpRequest extends FormRequest
{
    use NormalizesSyrianPhoneNumber;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeNormalizedSyrianPhoneNumber();
    }

    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', $this->syrianPhoneNumberRule()],
            'code' => ['required', 'digits:6'],
            'dive_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        $locale = $this->locale();

        $messages = [
            'ar' => [
                'phone_number.required' => 'رقم الهاتف مطلوب',
                'phone_number.string' => 'رقم الهاتف يجب أن يكون نصاً',
                'phone_number.regex' => 'صيغة رقم الهاتف غير صحيحة',
                'code.required' => 'رمز التحقق مطلوب',
                'code.digits' => 'رمز التحقق يجب أن يكون 6 أرقام',
                'dive_name.string' => 'اسم الجهاز يجب أن يكون نصاً',
                'dive_name.max' => 'اسم الجهاز يجب ألا يتجاوز 255 حرفاً',
            ],
            'en' => [
                'phone_number.required' => 'Phone number is required',
                'phone_number.string' => 'Phone number must be a string',
                'phone_number.regex' => 'Phone number format is invalid',
                'code.required' => 'OTP code is required',
                'code.digits' => 'OTP code must be 6 digits',
                'dive_name.string' => 'Device name must be a string',
                'dive_name.max' => 'Device name must not be greater than 255 characters',
            ],
        ];

        return $messages[$locale];
    }

    private function locale(): string
    {
        return str_starts_with(strtolower((string) $this->header('Accept-Language', 'ar')), 'en')
            ? 'en'
            : 'ar';
    }
}
