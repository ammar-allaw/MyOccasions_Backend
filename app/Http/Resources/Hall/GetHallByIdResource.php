<?php

namespace App\Http\Resources\Hall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GetHallByIdResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof \Illuminate\Support\Collection) {
            return $this->resource->map(function ($item) {
                return $this->transformCategory($item);
            })->toArray();
        }

        // Single item transformation
        return $this->transformCategory($this->resource);

    }

    private function transformCategory($item): array
    {
        $array = $item->toArray();
        // $decodeData=json_decode($item->data);
        $array=[
            'id'=>$item['id'],
            'name'=>$item['name'],
            'location'=>$item['location'],
            'image'=>url('storage/' . $item->getFirstMedia('logo_user')->id . '/' . $item->getFirstMedia('logo_user')->file_name),
            // 'rooms'=>[],
            // 'created_at' => Carbon::parse($item['created_at'])->format('Y/m/d'),
            // 'total_price_of_bill'=>$item['total_price_of_bill'],
        ];
        // $array['rooms']=[];
        // $array['services']=[];
    //    dd($item->rooms());
    if ($item->rooms->isNotEmpty()) {
        $array['rooms'] = $item->rooms->map(function ($room) {
            if ($room->serviceStatusAble && $room->serviceStatusAble->status_id == 1) {
                return [
                    'room_id' => $room->id,
                    'name' => $room->name,
                    'description' => $room->description,
                    'rent_price' => $room->rent_price,
                    'image_room' => $room->getMedia('images')->where('allowed','=',true)->map(function ($media) {
                        return url('storage/' . $media->id . '/' . $media->file_name);
                    }),
                ];
            }
        })->filter()->values(); // Use `values()` to reset the keys
    }
            if ($item->services->isNotEmpty()) {
                $array['service'] = $item->services->map(function ($service) {
                    if ($service->serviceStatusAble && $service->serviceStatusAble->status_id == 1) {

                        return [
                            'service_id' => $service->id,
                            'name' => $service->name,
                            'description' => $service->description,
                            'price' => $service->price,
                            'image_service'=> $mediaUrls = $service->getMedia('images')->where('allowed','=',true)->map(function ($media) {
                                return url('storage/' . $media->id . '/' . $media->file_name);
                            }),                        
                        ];
                    }
                })->filter()->values(); // Use `values()` to reset the keys
                
            }
        
        return $array;
    }
}
