<?php

namespace App\Http\Resources\Type;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class TypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $lang = $request->header('Accept-Language');
        if(Auth::guard('api')->check() && Auth::guard('api')->user()->is_provider==false)
        {
            return [
                'id' => $this->id,
                'name' => $lang === 'en' ? $this->name_en : $this->name,
                'role_id' => $this->role_id,
            ];
        } 
        return [
                'id' => $this->id,
                'name'=>$this->name,
                'name_en'=>$this->name_en,
                'role_id' => $this->role_id,
        ];  
    }
        
}
