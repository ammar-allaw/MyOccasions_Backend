<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizesSyrianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class LoginUserRequest extends FormRequest
{
    use NormalizesSyrianPhoneNumber;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeNormalizedSyrianPhoneNumber();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone_number'=>['required', 'string', $this->syrianPhoneNumberRule(), 'exists:users,phone_number'],
            'password'=>'required'
        ];
    }

    public function messages(): array
    {
        $messages = [
            'ar' => [
                'phone_number.required' => 'رقم الهاتف مطلوب',
                'phone_number.string' => 'رقم الهاتف يجب أن يكون نصاً',
                'phone_number.regex' => 'صيغة رقم الهاتف غير صحيحة',
                'phone_number.exists' => 'رقم الهاتف غير مسجل',
                'password.required' => 'كلمة المرور مطلوبة',
            ],
            'en' => [
                'phone_number.required' => 'Phone number is required',
                'phone_number.string' => 'Phone number must be a string',
                'phone_number.regex' => 'Phone number format is invalid',
                'phone_number.exists' => 'Phone number is not registered',
                'password.required' => 'Password is required',
            ],
        ];

        return $messages[$this->locale()];
    }

    private function locale(): string
    {
        $locale = $this->header(
            'localization',
            $this->header('localiztion', $this->header('Accept-Language', 'ar'))
        );

        return str_starts_with(strtolower((string) $locale), 'en') ? 'en' : 'ar';
    }
}
