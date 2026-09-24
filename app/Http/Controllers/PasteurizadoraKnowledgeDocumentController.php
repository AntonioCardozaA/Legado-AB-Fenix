<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePasteurizadoraKnowledgeDocumentRequest;
use App\Models\AnalisisPasteurizadora;
use App\Models\CentralHidraulicaComponente;
use App\Models\Linea;
use App\Models\PasteurizadoraKnowledgeDocument;
use App\Models\User;
use App\Services\Maintenance\DocumentIndexer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasteurizadoraKnowledgeDocumentController extends Controller
{
    public function __construct(
        private readonly DocumentIndexer $documentIndexer
    ) {
    }

    public function index(Request $request): View
    {
        $this->ensureAccess($request->user());

        $documents = PasteurizadoraKnowledgeDocument::query()
            ->with(['linea', 'uploadedBy'])
            ->withCount('chunks')
            ->when($request->filled('linea_id'), fn ($query) => $query->where('linea_id', (int) $request->input('linea_id')))
            ->when($request->filled('area'), fn ($query) => $query->where('area', $request->input('area')))
            ->when($request->filled('indexing_status'), fn ($query) => $query->where('indexing_status', $request->input('indexing_status')))
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $lineas = $this->pasteurizadoraLineas();
        $areas = $this->areaOptions($request->user());
        $componentes = $this->pasteurizadoraComponentes();
        $documentTypes = $this->documentTypes();

        return view('pasteurizadora.knowledge-documents.index', compact(
            'documents',
            'lineas',
            'areas',
            'componentes',
            'documentTypes'
        ));
    }

    public function create(Request $request): View
    {
        $this->ensureAccess($request->user());

        $lineas = $this->pasteurizadoraLineas();
        $areas = $this->areaOptions($request->user());
        $componentes = $this->pasteurizadoraComponentes();
        $documentTypes = $this->documentTypes();

        return view('pasteurizadora.knowledge-documents.create', compact(
            'lineas',
            'areas',
            'componentes',
            'documentTypes'
        ));
    }

    public function store(StorePasteurizadoraKnowledgeDocumentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->ensurePasteurizadoraScope($validated['linea_id'] ?? null, $validated['area'] ?? null, $request->user());
        $component = $this->resolveComponent($validated['component_code'] ?? null);

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

            $storagePath = $file->storeAs('pasteurizadora-knowledge', $filename, $storageDisk);
            $originalFilename = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
        }

        $document = PasteurizadoraKnowledgeDocument::create([
            'linea_id' => $validated['linea_id'] ?? null,
            'area' => $validated['area'] ?? null,
            'central_componente_id' => $component['central_componente_id'] ?? null,
            'component_code' => $component['component_code'] ?? ($validated['component_code'] ?? null),
            'component_name' => $component['component_name'] ?? ($validated['component_name'] ?? null),
            'modulo' => $validated['modulo'] ?? null,
            'nivel' => $validated['nivel'] ?? null,
            'piso' => $validated['piso'] ?? null,
            'lado' => $validated['lado'] ?? null,
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
            ->route('pasteurizadora.knowledge-documents.index')
            ->with('success', 'Documento cargado e indexado para la base de conocimiento de pasteurizadora.');
    }

    public function reindex(Request $request, PasteurizadoraKnowledgeDocument $document): RedirectResponse
    {
        $this->ensureAccess($request->user());
        $this->ensureDocumentBelongsToScope($document, $request->user());

        $this->documentIndexer->index($document);

        return back()->with('success', 'Documento reindexado correctamente.');
    }

    private function ensureAccess(?User $user): void
    {
        abort_unless(
            $user?->canManagePasteurizadoraKnowledgeDocuments(),
            403,
            'No tienes permiso para gestionar documentos de conocimiento de pasteurizadora.'
        );
    }

    private function ensurePasteurizadoraScope(?int $lineaId, ?string $area, ?User $user): void
    {
        if ($lineaId !== null) {
            $exists = Linea::query()
                ->whereKey($lineaId)
                ->whereIn('nombre', array_keys(AnalisisPasteurizadora::PASTEURIZADORES))
                ->exists();

            if (!$exists) {
                throw ValidationException::withMessages([
                    'linea_id' => 'Solo se permiten documentos asociados a lineas de pasteurizadora.',
                ]);
            }
        }

        if ($area && !($user?->canAccessPasteurizadoraArea($area) ?? false)) {
            throw ValidationException::withMessages([
                'area' => 'No tienes acceso al area seleccionada.',
            ]);
        }
    }

    private function ensureDocumentBelongsToScope(PasteurizadoraKnowledgeDocument $document, ?User $user): void
    {
        abort_unless(
            $document->linea_id === null || Linea::query()
                ->whereKey((int) $document->linea_id)
                ->whereIn('nombre', array_keys(AnalisisPasteurizadora::PASTEURIZADORES))
                ->exists(),
            404
        );

        abort_unless(!$document->area || ($user?->canAccessPasteurizadoraArea($document->area) ?? false), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveComponent(?string $componentCode): array
    {
        $componentCode = trim((string) $componentCode);

        if ($componentCode === '') {
            return [];
        }

        $central = CentralHidraulicaComponente::query()
            ->where('codigo', $componentCode)
            ->first();

        if ($central) {
            return [
                'central_componente_id' => $central->id,
                'component_code' => $central->codigo,
                'component_name' => $central->nombre_display,
            ];
        }

        foreach (array_keys(AnalisisPasteurizadora::PASTEURIZADORES) as $linea) {
            foreach (AnalisisPasteurizadora::getComponentesPorLinea($linea) as $code => $component) {
                if (Str::upper((string) $code) === Str::upper($componentCode)) {
                    return [
                        'component_code' => (string) $code,
                        'component_name' => (string) ($component['nombre'] ?? $code),
                    ];
                }
            }
        }

        return ['component_code' => $componentCode];
    }

    private function pasteurizadoraLineas()
    {
        return Linea::query()
            ->whereIn('nombre', array_keys(AnalisisPasteurizadora::PASTEURIZADORES))
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    /**
     * @return array<string, string>
     */
    private function areaOptions(?User $user): array
    {
        return collect([
            AnalisisPasteurizadora::AREA_MECANICA => 'Mecanica',
            AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA => 'Central hidraulica',
        ])
            ->filter(fn (string $label, string $area): bool => $user?->canAccessPasteurizadoraArea($area) ?? false)
            ->all();
    }

    /**
     * @return array<int, array{code: string, name: string, area: string}>
     */
    private function pasteurizadoraComponentes(): array
    {
        $componentes = [];

        foreach (array_keys(AnalisisPasteurizadora::PASTEURIZADORES) as $linea) {
            foreach (AnalisisPasteurizadora::getComponentesPorLinea($linea) as $code => $component) {
                $componentes[] = [
                    'code' => (string) $code,
                    'name' => (string) ($component['nombre'] ?? $code),
                    'area' => AnalisisPasteurizadora::AREA_MECANICA,
                ];
            }
        }

        CentralHidraulicaComponente::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->get()
            ->each(function (CentralHidraulicaComponente $component) use (&$componentes): void {
                $componentes[] = [
                    'code' => (string) $component->codigo,
                    'name' => $component->nombre_display,
                    'area' => AnalisisPasteurizadora::AREA_CENTRAL_HIDRAULICA,
                ];
            });

        return collect($componentes)
            ->unique(fn (array $component): string => $component['area'] . '|' . $component['code'])
            ->sortBy('name')
            ->values()
            ->all();
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
