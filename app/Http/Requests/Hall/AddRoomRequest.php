<?php

namespace App\Http\Requests\Hall;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AddRoomRequest extends FormRequest
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
                $this->uniqueRoomRule('name'),
            ],
            'description' => 'nullable|string|min:10|max:500',

            'name_en' => [
                'required',
                'string',
                $this->uniqueRoomRule('name_en'),

            ],
            'description_en' => 'nullable|string|min:10|max:500',

            'rent_price' => 'required|integer|min:0',
            'image.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', 
            'capacity' => 'required|integer|min:10',
        ];
    }

    protected function uniqueRoomRule(string $column)
    {
        $user_id = Auth::id();
        $user=User::find($user_id);
        if($user){
            $userable = $user->userable; 
            return Rule::unique('rooms', $column)
                ->where(function ($query) use ($userable) {
                    if ($userable instanceof \App\Models\ServiceProvider) {
                        $query->where('service_provider_id', $userable->id);
                    } 
                });
        }
    }
}