<?php

namespace App\Http\Requests;

use App\Models\AnalisisPasteurizadora;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePasteurizadoraKnowledgeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManagePasteurizadoraKnowledgeDocuments() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'linea_id' => ['nullable', 'exists:lineas,id'],
            'area' => ['nullable', Rule::in([
                AnalisisPasteurizadora::AREA_MECANICA,
                AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
            ])],
            'component_code' => ['nullable', 'string', 'max:120'],
            'component_name' => ['nullable', 'string', 'max:255'],
            'modulo' => ['nullable', 'integer', 'min:1', 'max:99'],
            'nivel' => ['nullable', 'string', 'max:50'],
            'piso' => ['nullable', 'string', 'max:50'],
            'lado' => ['nullable', 'string', 'max:50'],
            'document_type' => ['required', 'string', 'max:100'],
            'version' => ['nullable', 'string', 'max:80'],
            'effective_at' => ['nullable', 'date'],
            'lifecycle_status' => ['required', 'in:vigente,borrador,obsoleto'],
            'upload' => ['nullable', 'file', 'max:15360', 'extensions:txt,md,pdf,csv,html,htm,xml,log'],
            'extracted_text' => ['nullable', 'string', 'max:50000'],
            'metadata_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (!$this->hasFile('upload') && blank($this->input('extracted_text'))) {
                $validator->errors()->add('upload', 'Debes cargar un archivo o capturar el texto extraido.');
            }
        });
    }
}
