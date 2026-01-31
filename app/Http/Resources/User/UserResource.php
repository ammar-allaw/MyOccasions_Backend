<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // الحصول على المستخدم المصادق عليه
        $authUser = auth()->user();
        
        // الحصول على اللغة من الـ header
        $locale = $request->header('Accept-Language', 'ar');
        
        // تحديد اللغة المستخدمة
        // إذا كان المستخدم المصادق owner، نرجع عربي وإنجليزي
        // إذا لم يكن owner، نرجع حسب الـ header
        $isOwner = $authUser && $authUser->role && $authUser->role->name_en === 'owner';
        
        if ($isOwner) {
            // إذا كان owner، نرجع البيانات بالعربي والإنجليزي
            return [
                'id' => $this->id,
                'phone_number' => $this->phone_number,
                'role_id' => $this->role->id,
                'role_name_ar' => $this->role->name ?? $this->role->name_en,
                'role_name_en' => $this->role->name_en,
                'userable_id' => $this->userable_id,
                'userable_type' => $this->userable_type,
                'userable' => $this->formatUserableForOwner($this->userable->makeHidden('media')),
                'images' => $this->userable->getMedia('service_provider_image')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => url('storage/' . $media->id . '/' . $media->file_name),
                    ];
                })->toArray(),
                'order_status' => $this->getOrderStatus(),
            ];
        } else {
            // إذا لم يكن owner، نرجع حسب اللغة المطلوبة
            $data = [
                'id' => $this->id,
                'phone_number' => $this->phone_number,
                'role_id' => $this->role->id,
                // 'government_id' => $this->government_id,
                // 'government_name' => $this->government ? ($locale === 'en' ? $this->government->name_en : $this->government->name) : null,
                'role_name' => $locale === 'en'
                    ? $this->role->name_en
                    : ($this->role->name_ar ?? $this->role->name_en),
                'userable_id' => $this->userable_id,
                'userable_type' => $this->userable_type,
                'userable' => $this->formatUserableByLocale($this->userable->makeHidden('media'), $locale),
                'images' => $this->userable->getMedia('service_provider_image')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => url('storage/' . $media->id . '/' . $media->file_name),
                    ];
                })->toArray(),
                'models' => $this->getModelsInfo(),
            ];

            if ($this->userable_type === 'App\Models\Client' && $this->userable) {
                // Add government info directly to userable instead of root data
                $government = $this->userable->government;
                $data['userable']['government_id'] = $this->userable->government_id ?? null;
                $data['userable']['government_name'] = $government 
                    ? ($locale === 'en' ? $government->name_en : $government->name) 
                    : null;
            }

            if ($this->userable_type === 'App\Models\ServiceProvider' && $this->userable) {
                $region = $this->userable->region;
                $data['userable']['region_name'] = $region 
                    ? ($locale === 'en' ? $region->name_en : $region->name) 
                    : null;
            }

            return $data;
        }
    }

    /**
     * تنسيق الـ userable للمستخدم Owner (يرجع عربي وإنجليزي)
     */
    private function formatUserableForOwner($userable)
    {
        if (!$userable) {
            return null;
        }

        // نرجع جميع الحقول كما هي (بما فيها _ar و _en) مع إخفاء orderStatusAble
        $data = $userable->makeHidden(['orderStatusAble', 'order_status_able'])->toArray();
        return $data;
    }
    
    /**
     * تنسيق الـ userable حسب اللغة المطلوبة
     */
    private function formatUserableByLocale($userable, $locale)
    {
        if (!$userable) {
            return null;
        }
        
        $data = $userable->toArray();
        $result = [];
        
        foreach ($data as $key => $value) {
            // إذا كان الحقل ينتهي بـ _ar أو _en
            if (str_ends_with($key, '_ar')) {
                $baseKey = substr($key, 0, -3); // إزالة _ar
                if ($locale === 'ar') {
                    $result[$baseKey] = $value;
                }
            } elseif (str_ends_with($key, '_en')) {
                $baseKey = substr($key, 0, -3); // إزالة _en
                if ($locale === 'en') {
                    $result[$baseKey] = $value;
                }
            } else {
                // الحقول الأخرى نرجعها كما هي
                $result[$key] = $value;
            }
        }
        
        return $result;
    }

    /**
     * الحصول على حالة الـ order_status للـ service provider
     */
    private function getOrderStatus()
    {
        // التحقق من أن userable هو ServiceProvider
        if (!$this->userable || !method_exists($this->userable, 'orderStatusAble')) {
            return null;
        }

        $orderStatus = $this->userable->orderStatusAble;
        
        if (!$orderStatus) {
            return null;
        }

        return [
            'order_status_id' => $orderStatus->id,
            'status_id' => $orderStatus->status_id,
            'name' => $orderStatus->status->name ?? null,
            'name_en' => $orderStatus->status->name_en ?? null,
            'change_description' => $orderStatus->change_description,
            'last_modified_at' => $orderStatus->last_modified_at,
            'rejection_reason' => $orderStatus->rejection_reason,
        ];
    }

    /**
     * Get min/max capacity and price if the user is a hall.
     */
    private function getModelsInfo()
    {
        // Check if the user role is 'halls' and userable is ServiceProvider
        if ($this->role && $this->role->name_en === 'halls' && $this->userable_type === 'App\Models\ServiceProvider' && $this->userable) {
            $rooms = $this->userable->rooms;
            
            if ($rooms->isNotEmpty()) {
                return [
                    'min_capacity' => $rooms->min('capacity'),
                    'max_capacity' => $rooms->max('capacity'),
                    'min_price' => $rooms->min('rent_price'),
                    'max_price' => $rooms->max('rent_price'),
                    'count_rooms' => $rooms->count(),
                ];
            } else {
                // Return zeros if no rooms found
                return [
                    'min_capacity' => 0,
                    'max_capacity' => 0,
                    'min_price' => 0,
                    'max_price' => 0,
                    'count_rooms' => 0,
                ];
            }
        }
        return null;
    }
}
