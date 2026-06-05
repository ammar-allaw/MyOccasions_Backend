<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language', 'ar');
        $authUser = Auth::guard('api')->user();
        $isClient = $authUser && !$authUser->is_provider;

        if ($isClient) {
            return [
                'id' => $this->id,
                'name' => $locale === 'en' ? ($this->name_en ?? $this->name) : $this->name,
                'main_keys' => $this->whenLoaded('mainKeys', function () {
                    return MainKeyResource::collection($this->mainKeys);
                }, []),
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'main_keys' => $this->whenLoaded('mainKeys', function () {
                return MainKeyResource::collection($this->mainKeys);
            }, []),
        ];
    }
}
