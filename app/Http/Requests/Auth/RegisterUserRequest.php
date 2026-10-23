<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\NormalizesSyrianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
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
            'first_name'=>'required|string',
            'last_name'=>'required|string',
            'phone_number'=>['required', 'string', $this->syrianPhoneNumberRule(), 'unique:users,phone_number,except,id'],
            'role_id'=>'nullable|exists:roles,id',
            'password'=>'required|string|min:9|max:50|confirmed',
            'password_confirmation'=>'required|string|min:9|max:50',
            'government_id'=>'required|exists:governments,id',
        ];
    }

    public function messages(): array
    {
        $locale = $this->locale();

        $messages = [
            'ar' => [
                'first_name.required' => 'الاسم الأول مطلوب',
                'first_name.string' => 'الاسم الأول يجب أن يكون نصاً',
                'last_name.required' => 'الاسم الأخير مطلوب',
                'last_name.string' => 'الاسم الأخير يجب أن يكون نصاً',
                'phone_number.required' => 'رقم الهاتف مطلوب',
                'phone_number.string' => 'رقم الهاتف يجب أن يكون نصاً',
                'phone_number.regex' => 'صيغة رقم الهاتف غير صحيحة',
                'phone_number.unique' => 'رقم الهاتف مستخدم مسبقاً',
                'role_id.exists' => 'الدور المحدد غير صحيح',
                'password.required' => 'كلمة المرور مطلوبة',
                'password.string' => 'كلمة المرور يجب أن تكون نصاً',
                'password.min' => 'كلمة المرور يجب أن تكون 9 أحرف على الأقل',
                'password.max' => 'كلمة المرور يجب ألا تتجاوز 50 حرفاً',
                'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
                'password_confirmation.required' => 'تأكيد كلمة المرور مطلوب',
                'password_confirmation.string' => 'تأكيد كلمة المرور يجب أن يكون نصاً',
                'password_confirmation.min' => 'تأكيد كلمة المرور يجب أن يكون 9 أحرف على الأقل',
                'password_confirmation.max' => 'تأكيد كلمة المرور يجب ألا يتجاوز 50 حرفاً',
                'government_id.required' => 'المحافظة مطلوبة',
                'government_id.exists' => 'المحافظة المحددة غير صحيحة',
            ],
            'en' => [
                'first_name.required' => 'First name is required',
                'first_name.string' => 'First name must be a string',
                'last_name.required' => 'Last name is required',
                'last_name.string' => 'Last name must be a string',
                'phone_number.required' => 'Phone number is required',
                'phone_number.string' => 'Phone number must be a string',
                'phone_number.regex' => 'Phone number format is invalid',
                'phone_number.unique' => 'Phone number has already been taken',
                'role_id.exists' => 'Selected role is invalid',
                'password.required' => 'Password is required',
                'password.string' => 'Password must be a string',
                'password.min' => 'Password must be at least 9 characters',
                'password.max' => 'Password must not be greater than 50 characters',
                'password.confirmed' => 'Password confirmation does not match',
                'password_confirmation.required' => 'Password confirmation is required',
                'password_confirmation.string' => 'Password confirmation must be a string',
                'password_confirmation.min' => 'Password confirmation must be at least 9 characters',
                'password_confirmation.max' => 'Password confirmation must not be greater than 50 characters',
                'government_id.required' => 'Government is required',
                'government_id.exists' => 'Selected government is invalid',
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
