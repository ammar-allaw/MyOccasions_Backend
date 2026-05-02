<?php

namespace App\Http\Resources\Food;

use App\Http\Resources\Image\GetImageUrlResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class FoodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $authUser = Auth::guard('owner')->user() ?? Auth::guard('api')->user();
        $locale = $request->header('Accept-Language', 'ar');
        $isOwnerOrProvider = false;
        if ($authUser && method_exists($authUser, 'role')) {
            $roleName = $authUser->role->name_en ?? '';
            $isOwnerOrProvider = $roleName === 'owner' || ($authUser->is_provider ?? false);
        }
        $base = [
            'id' => $this->id,
            'slug' => $this->slug,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'is_available' => (int) $this->is_available,
            'is_active' => (int) $this->is_active,
            'preparation_time' => $this->preparation_time,
            'calories' => $this->calories,
            'ingredients' => $this->ingredients,
            'portion_size' => $this->portion_size,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'image' => $this->whenLoaded('media', function () {
                return GetImageUrlResource::collection(
                    $this->media->where('collection_name', 'food_image')
                );
            }, []),
        ];

        if ($isOwnerOrProvider) {
            $base['order_status'] = $this->whenLoaded('orderStatusAble', function () {
                return $this->orderStatusAble ? [
                    'order_status_id' => $this->orderStatusAble->id,
                    'status_id' => $this->orderStatusAble->status_id,
                    'name' => $this->orderStatusAble->status->name ?? null,
                    'name_en' => $this->orderStatusAble->status->name_en ?? null,
                    'rejection_reason' => $this->orderStatusAble->rejection_reason ?? null,
                ] : null;
            });
        }

        if ($isOwnerOrProvider) {
            return array_merge($base, [
                'name' => $this->name,
                'name_en' => $this->name_en,
                'description' => $this->description,
                'description_en' => $this->description_en,
                'short_description' => $this->short_description,
                'short_description_en' => $this->short_description_en,
                'main_key' => $this->whenLoaded('mainKey', function () {
                    return [
                        'id' => $this->mainKey->id,
                        'key' => $this->mainKey->key,
                        'key_en' => $this->mainKey->key_en,
                    ];
                }),
                'service_provider' => $this->whenLoaded('serviceProvider', function () {
                    return [
                        'id' => $this->serviceProvider->id,
                        'name' => $this->serviceProvider->name,
                        'name_en' => $this->serviceProvider->name_en,
                    ];
                }),
            ]);
        }

        return array_merge($base, [
            'name' => $locale === 'en' ? $this->name_en : $this->name,
            'description' => $locale === 'en' ? $this->description_en : $this->description,
            'short_description' => $locale === 'en' ? $this->short_description_en : $this->short_description,
            'main_key' => $this->whenLoaded('mainKey', function () use ($locale) {
                return [
                    'id' => $this->mainKey->id,
                    'key' => $locale === 'en' ? $this->mainKey->key_en : $this->mainKey->key,
                ];
            }),
        ]);
    }
}
