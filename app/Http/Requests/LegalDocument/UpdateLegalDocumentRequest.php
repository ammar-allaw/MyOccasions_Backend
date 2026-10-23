<?php

namespace App\Http\Requests\LegalDocument;

use App\Enums\LegalDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLegalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'required', 'string', Rule::in(LegalDocumentType::values())],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'title_en' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:50000'],
            'description_en' => ['sometimes', 'required', 'string', 'max:50000'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
