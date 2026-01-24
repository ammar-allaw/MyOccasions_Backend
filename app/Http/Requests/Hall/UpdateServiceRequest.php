<?php

namespace App\Http\Requests\Hall;

use App\Models\User;
use App\Rules\RoomBelongsToHall;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
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
                // $this->uniqueServiceRule('name'),
            ],
            'description' => 'nullable|string|min:10',

            'name_en' => [
                'nullable',
                'string',
                // $this->uniqueServiceRule('name_en'),

            ],
            'description_en' => 'nullable|string|min:10',

            'price' =>'nullable|integer|min:0',
            'room_id' => 'nullable|array',
            'room_id.*' => ['array', new RoomBelongsToHall],
            'image_id'=>'nullable|array',
            'image_id.*' => 'nullable|exists:media,id',
            'image' => 'nullable|array',
            'image.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'status_id' => 'nullable|exists:statuses,id',
            'rejection_reason' => 'nullable|string|required_if:status_id,2',
            'replace_all' => 'nullable|boolean'
        ];
    }

    protected function uniqueServiceRule(string $column)
    {
        $user_id = Auth::id();
        $user=User::find($user_id);
        if($user){
            $userable = $user->userable; 
            return Rule::unique('services', $column)
                ->where(function ($query) use ($userable) {
                    if ($userable instanceof \App\Models\ServiceProvider) {
                        $query->where('serviceable_id', $userable->id);
                    } 
                });
        }
    }
}
