<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWasherKnowledgeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageWasherKnowledgeDocuments() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'linea_id' => ['nullable', 'exists:lineas,id'],
            'componente_id' => ['nullable', 'exists:componentes,id'],
            'document_type' => ['required', 'string', 'max:100'],
            'version' => ['nullable', 'string', 'max:80'],
            'effective_at' => ['nullable', 'date'],
            'lifecycle_status' => ['required', 'in:vigente,borrador,obsoleto'],
            'upload' => ['nullable', 'file', 'max:15360', 'extensions:txt,md,pdf,doc,docx,csv,xls,xlsx,html,htm,xml,log'],
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
