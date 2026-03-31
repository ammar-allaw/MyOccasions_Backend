<?php
//new added
namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class AssignRoleToUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => 'required|integer|exists:roles,id',
            'allowed' => 'sometimes|boolean',
        ];
    }
}
