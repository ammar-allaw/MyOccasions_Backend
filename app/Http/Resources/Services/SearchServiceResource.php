<?php

namespace App\Http\Resources\Services;

use App\Http\Resources\Image\GetImageUrlResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class SearchServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $isOwner = false;

        // if (Auth::guard('owner')->check()) {
        //     $isOwner = true;
        // }

        // $user = auth('sanctum')->user();
        // if ($user) {
        //     if ($user->userable_type === $this->serviceable_type && $user->userable_id == $this->serviceable_id && $user->is_provider == true) {
        //         $isOwner = true;
        //     } elseif ($user->role && $user->role->name === 'owner') {
        //         $isOwner = true;
        //     }
        // }

        $locale = $request->header('Accept-Language', 'ar');
        $name = $locale === 'en' ? ($this->name_en ?? $this->name) : $this->name;
        $description = $locale === 'en' ? ($this->description_en ?? $this->description) : $this->description;

        $serviceProvider = $this->serviceable ?? null;
        $serviceProviderData = null;

        if ($serviceProvider) {
            $serviceProviderData = [
                'id' => $serviceProvider->user->id,
                'name' => $locale === 'en' ? ($serviceProvider->name_en ?? $serviceProvider->name) : $serviceProvider->name,
            ];
        }

        $data = [
            'id' => $this->id,
            'name' => $name,
            'description' => $description,
            'price' => $this->price,
            'serviceable_type' => $this->serviceable_type,
            'serviceable_id' => $this->serviceable_id,
            'service_provider' => $serviceProviderData,
            'images' => $this->whenLoaded('media', function () {
                $mainImages = $this->media->where('collection_name', 'service_image');
                return GetImageUrlResource::collection($mainImages);
            }, []),
            'gallery' => $this->whenLoaded('media', function () {
                $items = [];

                foreach ($this->media as $media) {
                    if ($media->collection_name === 'service_link_youtube') {
                        $items[] = [
                            'id' => $media->id,
                            'url' => $media->youtube_link,
                            'type' => 'video',
                        ];
                    } elseif ($media->collection_name === 'gallery') {
                        $items[] = [
                            'id' => $media->id,
                            'url' => url('storage/' . $media->id . '/' . $media->file_name),
                            'type' => 'gallery',
                        ];
                    }
                }

                return $items;
            }, []),
            'main_keys' => $this->whenLoaded('mainKeys', function () use ($locale) {
                return $this->mainKeys->map(fn ($key) => [
                    'id' => $key->id,
                    'key' => $locale === 'en' ? $key->key_en : $key->key,
                ]);
            }, []),
        ];

        // if ($isOwner) {
        //     $data['name_en'] = $this->name_en;
        //     $data['description_en'] = $this->description_en;
        //     $data['order_status'] = $this->whenLoaded('orderStatusAble', function () {
        //         return $this->orderStatusAble ? [
        //             'order_status_id' => $this->orderStatusAble->id,
        //             'status_id' => $this->orderStatusAble->status_id,
        //             'name' => $this->orderStatusAble->status->name ?? null,
        //             'name_en' => $this->orderStatusAble->status->name_en ?? null,
        //             'change_description' => $this->orderStatusAble->change_description,
        //             'last_modified_at' => $this->orderStatusAble->last_modified_at,
        //             'rejection_reason' => $this->orderStatusAble->rejection_reason,
        //         ] : null;
        //     });
        // }

        return $data;
    }
}
