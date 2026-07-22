<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWasherKnowledgeDocumentRequest;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use App\Models\WasherKnowledgeDocument;
use App\Services\Maintenance\DocumentIndexer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WasherKnowledgeDocumentController extends Controller
{
    private array $washerLineIds = [4, 5, 6, 7, 8, 9, 12, 13];

    public function __construct(
        private readonly DocumentIndexer $documentIndexer
    ) {
    }

    public function index(Request $request): View
    {
        $this->ensureAccess($request->user());

        $documents = WasherKnowledgeDocument::query()
            ->with(['linea', 'componente', 'uploadedBy'])
            ->withCount('chunks')
            ->when($request->filled('linea_id'), fn ($query) => $query->where('linea_id', (int) $request->input('linea_id')))
            ->when($request->filled('componente_id'), fn ($query) => $query->where('componente_id', (int) $request->input('componente_id')))
            ->when($request->filled('indexing_status'), fn ($query) => $query->where('indexing_status', $request->input('indexing_status')))
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $lineas = $this->lavadoraLineas();
        $componentes = $this->lavadoraComponentes();
        $documentTypes = $this->documentTypes();

        return view('lavadora.knowledge-documents.index', compact(
            'documents',
            'lineas',
            'componentes',
            'documentTypes'
        ));
    }

    public function create(Request $request): View
    {
        $this->ensureAccess($request->user());

        $lineas = $this->lavadoraLineas();
        $componentes = $this->lavadoraComponentes();
        $documentTypes = $this->documentTypes();

        return view('lavadora.knowledge-documents.create', compact(
            'lineas',
            'componentes',
            'documentTypes'
        ));
    }

    public function store(StoreWasherKnowledgeDocumentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->ensureLavadoraScope($validated['linea_id'] ?? null);

        $storagePath = null;
        $storageDisk = 'local';
        $originalFilename = null;
        $mimeType = null;

        if ($request->hasFile('upload')) {
            $file = $request->file('upload');
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = (string) Str::uuid();

            if ($extension !== '') {
                $filename .= '.' . $extension;
            }

            $storagePath = $file->storeAs('washer-knowledge', $filename, $storageDisk);
            $originalFilename = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
        }

        $document = WasherKnowledgeDocument::create([
            'linea_id' => $validated['linea_id'] ?? null,
            'componente_id' => $validated['componente_id'] ?? null,
            'title' => $validated['title'],
            'document_type' => $validated['document_type'],
            'version' => $validated['version'] ?? null,
            'effective_at' => $validated['effective_at'] ?? null,
            'lifecycle_status' => $validated['lifecycle_status'],
            'storage_disk' => $storageDisk,
            'storage_path' => $storagePath,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'uploaded_by' => $request->user()->id,
            'uploaded_at' => now(),
            'metadata' => [
                'notes' => $validated['metadata_notes'] ?? null,
            ],
            'indexing_status' => 'pending',
            'extracted_text' => $validated['extracted_text'] ?? null,
        ]);

        $this->documentIndexer->index($document);

        return redirect()
            ->route('lavadora.knowledge-documents.index')
            ->with('success', 'Documento cargado e indexado para la base de conocimiento de lavadoras.');
    }

    public function reindex(Request $request, WasherKnowledgeDocument $document): RedirectResponse
    {
        $this->ensureAccess($request->user());
        $this->ensureDocumentBelongsToScope($document);

        $this->documentIndexer->index($document);

        return back()->with('success', 'Documento reindexado correctamente.');
    }

    private function ensureAccess(?User $user): void
    {
        abort_unless(
            $user?->canManageWasherKnowledgeDocuments(),
            403,
            'No tienes permiso para gestionar documentos de conocimiento de lavadoras.'
        );
    }

    private function ensureLavadoraScope(?int $lineaId): void
    {
        if ($lineaId === null) {
            return;
        }

        if (!in_array($lineaId, $this->washerLineIds, true)) {
            throw ValidationException::withMessages([
                'linea_id' => 'Solo se permiten documentos asociados a lineas de lavadora.',
            ]);
        }
    }

    private function ensureDocumentBelongsToScope(WasherKnowledgeDocument $document): void
    {
        abort_unless(
            $document->linea_id === null || in_array((int) $document->linea_id, $this->washerLineIds, true),
            404
        );
    }

    private function lavadoraLineas()
    {
        return Linea::query()
            ->whereIn('id', $this->washerLineIds)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    private function lavadoraComponentes()
    {
        return Componente::query()
            ->where('activo', true)
            ->where(function ($query): void {
                $query->where('tipo_equipo', User::MODULE_LAVADORA)
                    ->orWhereNull('tipo_equipo');
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);
    }

    /**
     * @return array<string, string>
     */
    private function documentTypes(): array
    {
        return [
            'manual tecnico' => 'Manual tecnico',
            'manual de usuario' => 'Manual de usuario',
            'procedimiento' => 'Procedimiento',
            'estandar interno' => 'Estandar interno',
            'instructivo' => 'Instructivo',
            'plan anterior' => 'Plan anterior',
            'reporte' => 'Reporte',
        ];
    }
}
