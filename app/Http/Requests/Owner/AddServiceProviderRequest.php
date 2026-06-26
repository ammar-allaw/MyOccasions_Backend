<?php

namespace App\Http\Requests\Owner;

use App\Http\Requests\Concerns\NormalizesSyrianPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddServiceProviderRequest extends FormRequest
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

        if ($this->has('user_type') && ! is_array($this->user_type)) {
            $this->merge(['user_type' => [$this->user_type]]);
        }
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
            'phone_number'=>[
                'required',
                $this->syrianPhoneNumberRule(),
                Rule::unique('users', 'phone_number')->where(function ($query) {
                    return $query->where('role_id', $this->input('role_id'));
                }),
            ],
            'description'=>'nullable|string|min:5|max:500',
            'description_en'=>'nullable|string|min:5|max:500',
            'role_id'=>'required|exists:roles,id',
            'address_url'=>'nullable|string|url',
            'password'=>'required|string|min:9|max:50',
            'image'=>'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'user_type' => 'nullable|array',
            'user_type.*' => [
                'required',
                'integer',
                Rule::exists('types', 'id')->where(function ($query) {
                    return $query->where('role_id', $this->input('role_id'));
                }),
            ],
            'region_id'=>'nullable|exists:regions,id',
            'government_id'=>'required|exists:governments,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_type.*.exists' => 'The selected user type does not belong to the selected role.',
        ];
    }
}
