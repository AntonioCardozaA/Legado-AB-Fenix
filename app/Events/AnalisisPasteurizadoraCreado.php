<?php

namespace App\Events;

use App\Models\AnalisisPasteurizadora;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;

class AnalisisPasteurizadoraCreado
{
    use Dispatchable, InteractsWithSockets;

    public $analisis;

    public function __construct(AnalisisPasteurizadora $analisis)
    {
        $this->analisis = $analisis;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
