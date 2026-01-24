<?php

namespace App\Http\Resources\Hall;

use App\Http\Resources\Image\GetImageUrlResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // الحصول على المستخدم المصادق عليه من أي guard
        $authUser = auth()->guard('api')->user();
        
        if (!$authUser) {
            $authUser = auth()->guard('owner')->user();
        }
        
        // الحصول على اللغة من الـ header
        $locale = $request->header('Accept-Language', 'ar');
        
        // تحديد إذا كان المستخدم owner أو hall
        $isOwnerOrHall = false;
        if ($authUser) {
            // تحميل العلاقة role إذا لم تكن محملة
            if (!$authUser->relationLoaded('role')) {
                $authUser->load('role');
            }
            
            if ($authUser->role) {
                $roleName = $authUser->role->name_en ?? '';
                $isOwnerOrHall = in_array($roleName, ['owner', 'halls']);
            }
        }
        
        // إذا كان owner أو hall، نرجع كل شيء (عربي وإنجليزي)
        if ($isOwnerOrHall) {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'name_en' => $this->name_en,
                'description' => $this->description,
                'description_en' => $this->description_en,
                'rent_price' => $this->rent_price,
                'capacity' => $this->capacity,
                'service_provider_id' => $this->service_provider_id,
                'images' => $this->whenLoaded('media', function () {
                    return GetImageUrlResource::collection($this->media);
                }, []),
                // 'services' => $this->whenLoaded('services', function () {
                //     return ServiceResource::collection($this->services);
                // }, []),
                'order_status' => $this->whenLoaded('orderStatusAble', function () {
                    return $this->orderStatusAble ? [
                        'order_status_id' => $this->orderStatusAble->id,
                        'status_id' => $this->orderStatusAble->status_id,
                        'name' => $this->orderStatusAble->status->name ?? null,
                        'name_en' => $this->orderStatusAble->status->name_en ?? null,
                        'change_description' => $this->orderStatusAble->change_description,
                        'last_modified_at' => $this->orderStatusAble->last_modified_at,
                        'rejection_reason' => $this->orderStatusAble->rejection_reason,
                    ] : null;
                }),
            ];
        }
        
        // إذا لم يكن owner أو hall، نرجع حسب اللغة
        return [
            'id' => $this->id,
            'name' => $locale === 'en' ? $this->name_en : $this->name,
            'description' => $locale === 'en' ? $this->description_en : $this->description,
            'rent_price' => $this->rent_price,
            'capacity' => $this->capacity,
            'service_provider_id' => $this->service_provider_id,
            'images' => $this->whenLoaded('media', function () {
                return GetImageUrlResource::collection($this->media);
            }, []),
            'services' => $this->whenLoaded('services', function () {
                return ServiceResource::collection($this->services);
            }, []),
        ];
    }
}
