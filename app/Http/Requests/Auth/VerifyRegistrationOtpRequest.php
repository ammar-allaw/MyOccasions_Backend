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
}
