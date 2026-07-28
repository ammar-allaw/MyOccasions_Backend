<?php

namespace App\Http\Resources\LegalDocument;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class LegalDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isEnglish = $request->header('Accept-Language') === 'en';

        $data = [
            'id' => $this->id,
            'type' => $this->type?->value ?? $this->type,
            'is_active' => (bool) $this->is_active,
        ];

        if (Auth::guard('owner')->check()) {
            return [
                ...$data,
                'title' => $this->title,
                'title_en' => $this->title_en,
                'description' => $this->description,
                'description_en' => $this->description_en,
            ];
        }

        return [
            ...$data,
            'title' => $isEnglish ? $this->title_en : $this->title,
            'description' => $isEnglish ? $this->description_en : $this->description,
        ];
    }
}
