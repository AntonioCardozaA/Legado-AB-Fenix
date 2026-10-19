<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdminVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(User::ROLE_ADMIN) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'video' => [
                'required',
                'file',
                'mimes:mp4,webm,ogg,mov,avi,mkv',
                'max:512000',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'video' => 'video',
        ];
    }
}
