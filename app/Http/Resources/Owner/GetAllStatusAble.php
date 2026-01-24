<?php

namespace App\Http\Resources\Owner;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GetAllStatusAble extends JsonResource
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
    
        // Access the related model via the polymorphic relationship
        $relatedModel = $item->statusAble;
        
        // Get the name of the related model if it exists
        $name_of_model = $relatedModel ? $relatedModel->name ?? class_basename($relatedModel) : null;

        $array = [
            'id' => $item['id'],
            'status_id' => $item['status_id'],
            'statusable_id' => $item['statusable_id'],
            'statusable_type' => class_basename($relatedModel),
            'name_of_model' => $name_of_model, 
            'name_of_hall'=>$relatedModel->user->name,
            // 'photo_of_status_able'=>$relatedModel->getMedia('images')->where('allowed','=',false)->map(function ($media) {
            //     return url('storage/' . $media->id . '/' . $media->file_name);
            // }),

        ];
    
        return $array;
    }
    
}