<?php

namespace App\Http\Requests\LegalDocument;

use App\Enums\LegalDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLegalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(LegalDocumentType::values())],
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:50000'],
            'description_en' => ['required', 'string', 'max:50000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
