<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class MainKeyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language', 'ar');
        $authUser = Auth::guard('api')->user();
        $isClient = $authUser && !$authUser->is_provider;

        if ($isClient) {
            return [
                'id' => $this->id,
                'key' => $locale === 'en' ? ($this->key_en ?? $this->key) : $this->key,
                'role_id' => $this->role_id,
            ];
        }

        return [
            'id' => $this->id,
            'key' => $this->key,
            'key_en' => $this->key_en,
            'role_id' => $this->role_id,
        ];
    }
}
