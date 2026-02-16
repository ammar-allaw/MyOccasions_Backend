<?php

namespace App\Http\Resources\Services;

use App\Http\Resources\Image\GetImageUrlResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class ServicesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isOwner = false;
        
        if(Auth::guard('owner')->check())
        {
            $isOwner = true;
        }
        
        $user = auth('sanctum')->user();
        
        // Check if the authenticated user is the owner of this service or has 'owner' role
        if ($user) {
            // Check if user is the service provider who owns this service
            if ($user->userable_type === $this->serviceable_type && $user->userable_id == $this->serviceable_id) {
                $isOwner = true;
            }
            // Check if user has 'owner' role (assuming 'owner' is the role name for admins/owners)
            elseif ($user->role && $user->role->name === 'owner') {
                 $isOwner = true;
            }
        }

        $data = [
            'id' => $this->id,
            'price' => $this->price,
            'image' => $this->getFirstMedia('service_image') ? new GetImageUrlResource($this->getFirstMedia('service_image')) : null,
        ];

        // Status for Owner/Provider
        if ($isOwner) {
             $data['status'] = $this->orderStatusAble->status->name ?? 'N/A';
             $data['status_en'] = $this->orderStatusAble->status->name_en ?? 'N/A';
             $data['rejection_reason'] = $this->orderStatusAble->rejection_reason ?? null;
             $data['change_description'] = $this->orderStatusAble->change_description ?? null;

        }

        // Always include localized fields for display
        $locale = $request->header('Accept-Language', 'ar');
        $thisName = $locale === 'ar' ? $this->name : ($this->name_en ?? $this->name);
        $thisDescription = $locale === 'ar' ? $this->description : ($this->description_en ?? $this->description);

        // If owner/provider, return bilingual fields
        if ($isOwner) {
            $data['name'] = $this->name; // Arabic
            $data['name_en'] = $this->name_en;
            $data['description'] = $this->description; // Arabic
            $data['description_en'] = $this->description_en;
        } else {
            $data['name'] = $thisName;
            $data['description'] = $thisDescription;
        }

        return $data;
    }
}
