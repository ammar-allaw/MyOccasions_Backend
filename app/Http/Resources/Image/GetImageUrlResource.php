<?php

namespace App\Http\Resources\Image;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GetImageUrlResource extends JsonResource
{

    // private $imageType;
    // public function __construct($imageType)
    // {
    //     $imageType=$imageType;
    // }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
          return [
            
            // 'images' => $this->getMedia($this->imageType)->map(function ($media) {
            // return [
                'id' => $this->id,
                'url' => url('storage/' . $this->id . '/' . $this->file_name), // this generates correct URL automatically
            ];
            // })->toArray(),
        // ]; 
    }
}
