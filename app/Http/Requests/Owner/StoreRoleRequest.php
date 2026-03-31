<?php
//new added
namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:255|unique:roles,name',
            'name_en' => 'required|string|max:255|unique:roles,name_en',
        ];
    }
}
