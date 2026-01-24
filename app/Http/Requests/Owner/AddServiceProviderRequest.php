<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class AddServiceProviderRequest extends FormRequest
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
            'name'=>'required|string|min:3|max:200|unique:service_providers,name,except,id',
            'name_en'=>'required|string|min:3|max:200|unique:service_providers,name_en,except,id',
            'location'=>'required|string|min:5|max:250',
            'location_en'=>'required|string|min:5|max:250',
            'phone_number'=>'required|unique:users,phone_number,except,id|regex:/^09[0-9]{8}$/',
            'description'=>'nullable|string|min:5|max:500',
            'description_en'=>'nullable|string|min:5|max:500',
            'role_id'=>'required|exists:roles,id',
            'address_url'=>'nullable|string|url',
            'password'=>'required|string|min:9|max:50',
            'image'=>'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'user_type' => 'nullable|array', 
            'user_type.*'=>'nullable',
            'region_id'=>'nullable|exists:regions,id',
            'government_id'=>'required|exists:governments,id',
        ];
    }
}
