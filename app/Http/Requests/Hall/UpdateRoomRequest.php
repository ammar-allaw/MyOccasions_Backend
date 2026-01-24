<?php

namespace App\Http\Requests\Hall;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
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
                'nullable',
                'string',
                $this->uniqueRoomRule('name'),
            ],
            'description' => 'nullable|string|min:10',

            'name_en' => [
                'nullable',
                'string',
                $this->uniqueRoomRule('name_en'),

            ],
            'description_en' => 'nullable|string|min:10',

            'rent_price' => 'nullable|integer|min:0',
            'image' => 'nullable',
            'image.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
            'capacity' => 'nullable|integer|min:10',
            'status_id' => 'nullable|exists:statuses,id',
            'rejection_reason' => 'nullable|string|required_if:status_id,2',
            'image' => 'nullable|array',
            'image_id'=>'nullable|array',
            'image_id.*' => 'nullable|exists:media,id',
            'replace_all' => 'nullable|boolean'
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
