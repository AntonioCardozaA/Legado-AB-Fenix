<?php

namespace App\Listeners;

use App\Events\AnalisisPasteurizadoraCreado;
use App\Models\HistoricoRevisados;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ActualizarHistoricoRevisados implements ShouldQueue
{
    use InteractsWithQueue;

    public $delay = 5;

    public function handle(AnalisisPasteurizadoraCreado $event)
    {
        $analisis = $event->analisis;

        // Actualizar el histórico de revisados
        HistoricoRevisados::actualizarDesdePasteurizadora(
            $analisis->linea,
            $analisis->componente
        );
    }
}
