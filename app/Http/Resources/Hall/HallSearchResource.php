<?php

namespace App\Http\Resources\Hall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HallSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language', 'ar');
        // $this is the User model. $this->userable is the ServiceProvider.
        $user = $this;
        $serviceProvider = $user->userable;

        $hallName = null;
        $hallImage = null;
        $matchingRoom = null;

        if ($serviceProvider) {
            $hallName = $locale === 'en' ? $serviceProvider->name_en : $serviceProvider->name;
            
            // Get one image for the hall
            // Primary collection: 'service_provider_image'
            $hallImage = $serviceProvider->getFirstMediaUrl('service_provider_image');
            if (empty($hallImage)) {
                $hallImage = $serviceProvider->getFirstMediaUrl('images');
            }
            if (empty($hallImage)) {
                 $hallImage = $serviceProvider->getFirstMediaUrl('default');
            }

            $rooms = $serviceProvider->rooms;
            
            // Filter logic to find matched room
            if ($request->has('price') || $request->has('capacity')) {
                 $rooms = $rooms->filter(function ($room) use ($request) {
                    $matchPrice = true;
                    $matchCapacity = true;

                    if ($request->has('price')) {
                         $price = $request->price;
                         $op = $request->get('price_operator', 'less');
                         if ($op === 'more') {
                             $matchPrice = $room->rent_price >= $price;
                         } else {
                             $matchPrice = $room->rent_price <= $price;
                         }
                    }

                    if ($request->has('capacity')) {
                         $cap = $request->capacity;
                         $op = $request->get('capacity_operator', 'less');
                         if ($op === 'more') {
                             $matchCapacity = $room->capacity >= $cap;
                         } else {
                             $matchCapacity = $room->capacity <= $cap;
                         }
                    }

                    return $matchPrice && $matchCapacity;
                });
            }
            
            $matchingRoom = $rooms->first();
        }

        $roomDetails = null;
        if ($matchingRoom) {
            $roomDetails = [
                'id' => $matchingRoom->id,
                'name' => $locale === 'en' ? $matchingRoom->name_en : $matchingRoom->name,
                'description' => $locale === 'en' ? $matchingRoom->description_en : $matchingRoom->description,
                'price' => $matchingRoom->rent_price,
                'capacity' => $matchingRoom->capacity,
                'image' => $matchingRoom->getFirstMediaUrl('images'),
            ];
        }

        return [
            'id' => $user->id,
            'hall_name' => $hallName,
            'hall_image' => $hallImage,
            'room' => $roomDetails,
        ];
    }
}
