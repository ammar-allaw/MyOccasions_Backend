<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
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
            'first_name'=>'required|string',
            'last_name'=>'required|string',
            'phone_number'=>'required|string|unique:users,phone_number,except,id',
            'role_id'=>'nullable,exists:roles,id',
            'password'=>'required|string|min:9|max:50',
            'password_confirmation'=>'required|string|min:9|max:50,confirmed',
            'government_id'=>'required|exists:governments,id',
        ];
    }
}
