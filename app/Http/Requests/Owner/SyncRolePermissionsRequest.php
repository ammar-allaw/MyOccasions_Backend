<?php
//new added
namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_ids'   => 'required|array|min:1',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ];
    }
}
