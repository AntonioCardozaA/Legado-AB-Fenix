<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssistantMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
            'page_context' => ['sometimes', 'array'],
            'page_context.page_title' => ['nullable', 'string', 'max:255'],
            'page_context.current_url' => ['nullable', 'string', 'max:500'],
            'page_context.current_path' => ['nullable', 'string', 'max:255'],
            'page_context.module' => ['nullable', 'string', Rule::in([
                User::MODULE_LAVADORA,
                User::MODULE_ETIQUETADORA,
                User::MODULE_PASTEURIZADORA,
            ])],
            'page_context.section' => ['nullable', 'string', 'max:255'],
            'page_context.entity_label' => ['nullable', 'string', 'max:255'],
            'page_context.record_id' => ['nullable', 'integer'],
        ];
    }
}
