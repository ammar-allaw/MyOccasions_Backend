<?php

namespace App\Http\Resources\Concerns;

use App\Models\ServiceProvider;

trait FormatsServiceProviderContact
{
    protected function serviceProviderContactFields($serviceProvider, $user = null): array
    {
        if (! $serviceProvider instanceof ServiceProvider) {
            return [];
        }

        $mobilePhone = $user?->phone_number ?? $serviceProvider?->user?->phone_number;
        $landlinePhone = $serviceProvider?->landline_phone;
        $useLandline = (bool) ($serviceProvider?->use_landline_for_calls ?? false);

        return [
            'phone_number' => $mobilePhone,
            'landline_phone' => $landlinePhone,
            'use_landline_for_calls' => $useLandline,
            'call_phone_number' => $useLandline && ! empty($landlinePhone)
                ? $landlinePhone
                : $mobilePhone,
        ];
    }
}
