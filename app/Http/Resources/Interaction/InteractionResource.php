<?php

namespace App\Http\Resources\Interaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InteractionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this['type'],
            'id' => $this['id'],
            'state' => $this['state'],
            'views_count' => $this['views_count'],
            'likes_count' => $this['likes_count'],
            'is_liked' => $this['is_liked'],
        ];
    }
}
