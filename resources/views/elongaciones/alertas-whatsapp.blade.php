@extends('layouts.app')

@section('title', 'Alertas WhatsApp de Elongacion')

@section('content')
@php
    $formatRemainingTime = static function (int $daysRemaining): string {
        return match (true) {
            $daysRemaining > 1 => "faltan {$daysRemaining} dias",
            $daysRemaining === 1 => 'falta 1 dia',
            $daysRemaining === 0 => 'vence hoy',
            $daysRemaining === -1 => 'vencida por 1 dia',
            default => 'vencida por ' . abs($daysRemaining) . ' dias',
        };
    };

    $maskPhone = static function (?string $number): string {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';
        $length = strlen($digits);

        if ($length <= 5) {
            return str_repeat('*', $length);
        }

        return substr($digits, 0, 3) . str_repeat('*', max($length - 5, 0)) . substr($digits, -2);
    };

    $statusLabel = static fn (?string $status): string => match ($status) {
        'sent' => 'Enviado',
        'failed' => 'Fallido',
        'processing' => 'Procesando',
        'pending' => 'Pendiente',
        default => $status ? ucfirst($status) : 'Sin estado',
    };

    $statusClass = static fn (?string $status): string => match ($status) {
        'sent' => 'bg-emerald-100 text-emerald-800',
        'failed' => 'bg-red-100 text-red-800',
        'processing' => 'bg-amber-100 text-amber-800',
        default => 'bg-slate-100 text-slate-700',
    };

    $channelLabel = static fn (?string $channel): string => match ($channel) {
        'whatsapp' => 'Automatico',
        'whatsapp_manual' => 'Manual',
        default => $channel ?: 'WhatsApp',
    };

    $latestAutomatic = $automaticNotifications->first();
    $latestManual = $manualNotifications->first();
    $automaticCardText = $hasAutomaticSent ? 'Si se mando' : ($latestAutomatic ? $statusLabel($latestAutomatic->status) : 'No enviado');
    $manualCardText = $manualNotifications->contains('status', 'sent') ? 'Si se mando' : ($latestManual ? $statusLabel($latestManual->status) : 'No enviado');
@endphp

<div class="mx-auto max-w-7xl space-y-6 px-4 py-8">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <a href="{{ route('elongaciones.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900">
                    <i class="fas fa-arrow-left"></i>
                    Volver a elongaciones
                </a>
                <h1 class="flex items-center gap-3 text-2xl font-extrabold text-slate-900 sm:text-3xl">
                    <i class="fab fa-whatsapp text-emerald-600"></i>
                    Alertas WhatsApp de elongacion
                </h1>
                <p class="mt-2 text-sm text-slate-500">
                    Revision del {{ $referenceDate->format('d/m/Y') }}
                </p>
            </div>

            <form method="GET" action="{{ route('elongaciones.alertas-whatsapp.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div>
                    <label for="date" class="mb-1 block text-sm font-bold text-slate-700">Fecha</label>
                    <input
                        id="date"
                        type="date"
                        name="date"
                        value="{{ $referenceDate->toDateString() }}"
                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800">
                    <i class="fas fa-magnifying-glass"></i>
                    Consultar
                </button>
            </form>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
            <i class="fas fa-triangle-exclamation mr-2"></i>{{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
            <i class="fas fa-circle-xmark mr-2"></i>{{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">Revisa el numero seleccionado antes de enviar.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Lineas pendientes</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $pendingAlerts->count() }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Automatico</p>
            <p class="mt-2 text-2xl font-extrabold {{ $hasAutomaticSent ? 'text-emerald-700' : 'text-slate-900' }}">
                {{ $automaticCardText }}
            </p>
            <p class="mt-1 text-xs font-medium text-slate-500">
                {{ $latestAutomatic?->updated_at?->format('d/m/Y H:i') ?? 'Sin intento registrado' }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">Manual</p>
            <p class="mt-2 text-2xl font-extrabold {{ $manualNotifications->contains('status', 'sent') ? 'text-emerald-700' : 'text-slate-900' }}">
                {{ $manualCardText }}
            </p>
            <p class="mt-1 text-xs font-medium text-slate-500">
                {{ $latestManual?->updated_at?->format('d/m/Y H:i') ?? 'Sin intento manual' }}
            </p>
        </div>

        <div class="rounded-xl border {{ $hasAnySent ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} p-5 shadow-sm">
            <p class="text-sm font-semibold {{ $hasAnySent ? 'text-emerald-700' : 'text-amber-700' }}">Resultado</p>
            <p class="mt-2 text-2xl font-extrabold {{ $hasAnySent ? 'text-emerald-900' : 'text-amber-900' }}">
                {{ $hasAnySent ? 'Alerta enviada' : 'Sin envio confirmado' }}
            </p>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1fr_420px]">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-lg font-extrabold text-slate-900">Lineas que generan alerta</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Linea</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Ultimo registro</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Vence</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Ciclo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($pendingAlerts as $alert)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-800">{{ $alert['linea'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $alert['last_recorded_at']->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ $alert['due_at']->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-sm font-semibold {{ $alert['days_remaining'] < 0 ? 'text-red-700' : 'text-orange-700' }}">
                                    {{ $formatRemainingTime((int) $alert['days_remaining']) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $alert['ciclo'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm font-medium text-slate-500">
                                    No hay lineas pendientes para esta fecha.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('elongaciones.alertas-whatsapp.send') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <input type="hidden" name="notification_date" value="{{ $referenceDate->toDateString() }}">

            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                    <i class="fab fa-whatsapp"></i>
                </span>
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">Envio manual</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $referenceDate->format('d/m/Y') }}</p>
                </div>
            </div>

            <div class="mt-5 space-y-4">
                <div>
                    <label for="recipient" class="mb-1 block text-sm font-bold text-slate-700">Numero configurado</label>
                    <select
                        id="recipient"
                        name="recipient"
                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                        <option value="">Seleccionar numero</option>
                        @foreach($recipientOptions as $option)
                            <option value="{{ $option['number'] }}" {{ old('recipient') === $option['number'] ? 'selected' : '' }}>
                                {{ $option['masked'] }} - {{ implode(', ', $option['sources']) }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('recipient')" />
                </div>

                <div>
                    <label for="custom_recipient" class="mb-1 block text-sm font-bold text-slate-700">Numero manual</label>
                    <input
                        id="custom_recipient"
                        type="text"
                        name="custom_recipient"
                        value="{{ old('custom_recipient') }}"
                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                        placeholder="+52 498 109 6696"
                    >
                    <x-input-error class="mt-2" :messages="$errors->get('custom_recipient')" />
                </div>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-bold text-white transition {{ $pendingAlerts->isEmpty() ? 'cursor-not-allowed bg-slate-300' : 'bg-emerald-600 hover:bg-emerald-700' }}"
                    {{ $pendingAlerts->isEmpty() ? 'disabled' : '' }}
                >
                    <i class="fas fa-paper-plane"></i>
                    Mandar alerta
                </button>
            </div>
        </form>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-lg font-extrabold text-slate-900">Comprobante de envios</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Canal</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Numero</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Lineas</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">Respuesta</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($notifications as $notification)
                        @php
                            $lineas = collect($notification->lines_snapshot ?? [])->pluck('linea')->filter()->implode(', ');
                            $ultramsgStatus = data_get($notification->metadata, 'ultramsg_status');
                            $ultramsgResponse = data_get($notification->metadata, 'ultramsg_response');
                            $ultramsgSummary = is_array($ultramsgResponse)
                                ? json_encode($ultramsgResponse, JSON_UNESCAPED_UNICODE)
                                : (string) $ultramsgResponse;
                            $eventDate = $notification->sent_at ?: ($notification->failed_at ?: $notification->updated_at);
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-sm font-semibold text-slate-800">{{ $channelLabel($notification->channel) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $maskPhone($notification->recipient) }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass($notification->status) }}">
                                    {{ $statusLabel($notification->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $lineas !== '' ? $lineas : '-' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $eventDate?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="max-w-md px-4 py-3 text-sm text-slate-600">
                                @if($notification->error_message)
                                    <span class="text-red-700">{{ $notification->error_message }}</span>
                                @elseif($ultramsgStatus)
                                    UltraMsg HTTP {{ $ultramsgStatus }}
                                    @if($ultramsgSummary !== '')
                                        <span class="mt-1 block text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($ultramsgSummary, 180) }}</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm font-medium text-slate-500">
                                Sin intentos de WhatsApp registrados para esta fecha.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
