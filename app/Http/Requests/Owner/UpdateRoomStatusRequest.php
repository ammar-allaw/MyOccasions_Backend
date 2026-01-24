<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomStatusRequest extends FormRequest
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
            'status_id' => 'required|exists:statuses,id',
        ];
    }

    public function messages(): array
    {
        return [
            'status_id.required' => 'حقل الحالة مطلوب',
            'status_id.exists' => 'الحالة المحددة غير موجودة',
        ];
    }
}
