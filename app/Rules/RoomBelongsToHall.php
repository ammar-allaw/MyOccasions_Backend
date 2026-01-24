<?php

namespace App\Rules;

use App\Models\Room;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class RoomBelongsToHall implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $userId = Auth::id();
        $user=User::find($userId);
        if($user)
        {
            $hall = $user?->userable; // Assuming userable is Hall/ServiceProvider

        }

        if (!$hall) {
            $fail("You are not associated with any hall.");
            return;
        }

        $exists = Room::where('id', $value)
            ->where('service_provider_id', $hall->id) // adjust column name if different
            ->exists();

        if (!$exists) {
            $fail("The selected room does not belong to your hall.");
        }
    }
}
