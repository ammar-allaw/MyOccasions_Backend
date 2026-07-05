<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->header(
            'localization',
            $request->header('localiztion', $request->header('Accept-Language', 'ar'))
        );
        $locale = str_starts_with(strtolower((string) $locale), 'en') ? 'en' : 'ar';

        $client = $this->userable;
        $government = $client?->government;

        return [
            'id' => $this->id,
            'first_name' => $client?->first_name,
            'last_name' => $client?->last_name,
            'full_name' => trim(($client?->first_name ?? '').' '.($client?->last_name ?? '')),
            'phone_number' => $this->phone_number,
            'government' => $government ? [
                'id' => $government->id,
                'name' => $locale === 'en' ? $government->name_en : $government->name,
            ] : null,
        ];
    }
}
