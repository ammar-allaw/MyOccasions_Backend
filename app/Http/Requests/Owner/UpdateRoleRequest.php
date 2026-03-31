<?php
//new added
namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('roleId');

        return [
            'name'    => "sometimes|required|string|max:255|unique:roles,name,{$roleId}",
            'name_en' => "sometimes|required|string|max:255|unique:roles,name_en,{$roleId}",
        ];
    }
}
