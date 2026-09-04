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
            'message' => ['required', 'string', 'max:5000'],
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
            'page_context.linea_nombre' => ['nullable', 'string', 'max:80'],
            'page_context.area' => ['nullable', 'string', 'max:80'],
            'page_context.area_pasteurizadora' => ['nullable', 'string', 'max:80'],
            'page_context.component_name' => ['nullable', 'string', 'max:160'],
            'page_context.component_code' => ['nullable', 'string', 'max:80'],
            'page_context.configuracion_id' => ['nullable', 'integer'],
            'page_context.modulo' => ['nullable', 'integer'],
            'page_context.nivel' => ['nullable', 'string', 'max:40'],
            'page_context.piso' => ['nullable', 'string', 'max:40'],
            'page_context.lado' => ['nullable', 'string', 'max:40'],
            'page_context.record_id' => ['nullable', 'integer'],
        ];
    }
}
