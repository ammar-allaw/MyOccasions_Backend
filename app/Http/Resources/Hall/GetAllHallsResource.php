<?php

namespace App\Http\Resources\Hall;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GetAllHallsResource extends JsonResource
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
        $image = $item->getFirstMedia('logo_user');

        return [
            'id' => $item->id,
            'name' => $item->name,
            'location' => $item->location,
            'image' => $image ? url('storage/' . $image->id . '/' . $image->file_name) : null,
        ];
    }
}

