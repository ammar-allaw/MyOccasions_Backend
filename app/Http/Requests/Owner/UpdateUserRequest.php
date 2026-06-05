<?php

namespace App\Http\Requests\Owner;

use App\Http\Requests\Concerns\NormalizesSyrianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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

    //we dint use this request 
    public function rules(): array
    {
        return [
            'name'=>'nullable|string|unique:users,name,except,id',
            'name_ar'=>'nullable|string|min:3|max:200|unique:users,name_ar,except,id',
            'email'=>'nullable|email|unique:users,email,except,id',
            'location'=>'nullable|string',
            'location_ar'=>'nullable|string|min:5|max:250',
            'phone_number'=>['nullable', $this->syrianPhoneNumberRule(), 'unique:users,phone_number,except,id'],
            'description'=>'nullable|string',
            'description_ar'=>'nullable|string|min:10|max:500',
            'role_id'=>'nullable|exists:roles,id',
            'is_admin'=>'nullable|boolean',
            'image'=>'nullable|image'
        ];
    }
}
