<?php

namespace App\Http\Requests\Hall;

use App\Models\User;
use App\Rules\RoomBelongsToHall;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AddServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
     public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                // $this->uniqueRoomRule('name'),
            ],
            'description' => 'nullable|string|min:10|max:500',

            'name_en' => [
                'required',
                'string',
                // $this->uniqueRoomRule('name_en'),

            ],
            'description_en' => 'nullable|string|min:10|max:500',

            'price' =>'required|integer|min:0',
            'room_id' => 'nullable|array',
            'room_id.*' => ['integer', new RoomBelongsToHall],
            'image' => 'required|array',
            'image.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    // protected function uniqueRoomRule(string $column)
    // {
    //     $user_id = Auth::id();
    //     $user=User::find($user_id);
    //     if($user){
    //         $userable = $user->userable; 
    //         return Rule::unique('services', $column)
    //             ->where(function ($query) use ($userable) {
    //                 if ($userable instanceof \App\Models\ServiceProvider) {
    //                     $query->where('serviceable_id', $userable->id);
    //                 } 
    //             });
    //     }
    // }
}