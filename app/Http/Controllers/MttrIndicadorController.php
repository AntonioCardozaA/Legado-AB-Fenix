<?php

namespace App\Http\Controllers;

use App\Models\MttrIndicador;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MttrIndicadorController extends Controller
{
    public function index(): View
    {
        $indicadores = MttrIndicador::orderBy('id')->get()->keyBy('clave');

        return view('admin.mttr.index', [
            'indicadores' => $indicadores,
            'revision' => $this->revision($indicadores),
            'ultimaActualizacion' => $indicadores->max('updated_at'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $etiquetas = [MttrIndicador::GENERAL => 'MTTR general'];
        foreach (MttrIndicador::LAVADORAS as $lavadora) {
            $etiquetas[$lavadora] = "MTTR de {$lavadora}";
        }

        $datos = $request->validate([
            'revision' => ['required', 'string', 'size:64'],
            'minutos' => ['required', 'array:'.implode(',', array_keys($etiquetas))],
            'minutos.*' => ['bail', 'nullable', 'numeric', 'decimal:0,4', 'min:0', 'max:'.MttrIndicador::MAXIMO_MINUTOS],
        ], [
            'revision.*' => 'Vuelve a abrir el formulario de MTTR para guardar los cambios.',
            'minutos.required' => 'El formulario debe incluir los indicadores de MTTR.',
            'minutos.array' => 'El formulario contiene un indicador de MTTR no válido.',
            'minutos.*.numeric' => 'El :attribute debe ser un número.',
            'minutos.*.decimal' => 'El :attribute admite hasta cuatro decimales.',
            'minutos.*.min' => 'El :attribute debe ser mayor o igual a cero.',
            'minutos.*.max' => 'El :attribute debe ser menor o igual a :max minutos.',
        ], collect($etiquetas)->mapWithKeys(fn ($etiqueta, $clave) => ["minutos.{$clave}" => $etiqueta])->all());

        $usuarioId = $request->user()->id;

        try {
            $cambios = DB::transaction(function () use ($datos, $usuarioId): ?int {
                $indicadores = MttrIndicador::orderBy('id')->lockForUpdate()->get()->keyBy('clave');
                if ($this->revision($indicadores) !== $datos['revision']) {
                    return null;
                }

                $actualizaciones = [];
                foreach ($datos['minutos'] as $clave => $minutos) {
                    if ($minutos === null) {
                        continue;
                    }

                    $indicador = $indicadores->get($clave);
                    $anterior = $indicador->minutos;
                    $indicador->minutos = $minutos;

                    if (! $indicador->isDirty('minutos')) {
                        continue;
                    }

                    $indicador->actualizado_por = $usuarioId;
                    $indicador->save();

                    $actualizaciones[] = [
                        'indicador_id' => $indicador->id,
                        'minutos_anteriores' => $anterior,
                        'minutos_nuevos' => $indicador->minutos,
                        'usuario_id' => $usuarioId,
                        'created_at' => $indicador->updated_at,
                    ];
                }

                if ($actualizaciones !== []) {
                    DB::table('mttr_actualizaciones')->insert($actualizaciones);
                }

                return count($actualizaciones);
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            return to_route('admin.mttr.index')->withInput()->withErrors([
                'minutos' => 'Ocurrió un error al guardar el MTTR. Vuelve a intentarlo.',
            ]);
        }

        if ($cambios === null) {
            return to_route('admin.mttr.index')->withErrors([
                'revision' => 'Los indicadores cambiaron desde que abriste el formulario. Revisa los valores actuales y vuelve a guardar.',
            ]);
        }

        return to_route('admin.mttr.index')->with('mttr_status', $cambios > 0
            ? 'Cambios de MTTR guardados correctamente.'
            : 'Los valores de MTTR siguen iguales.');
    }

    private function revision(Collection $indicadores): string
    {
        return hash('sha256', $indicadores->map->minutos->toJson());
    }
}
