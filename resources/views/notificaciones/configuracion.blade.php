@extends('layouts.app')

@section('title', 'Configuracion de Notificaciones')

@section('content')
@php
    $user = Auth::user();
    $selectedLines = collect($settings->lines_to_notify ?? [])->map(fn ($lineId) => (int) $lineId)->all();
    $preferences = $settings->preferences ?? [];
@endphp

<div class="mx-auto max-w-6xl space-y-6">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-200/70">
        <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-blue-700 px-5 py-6 text-white sm:px-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-blue-100">
                        <i class="fas fa-bell"></i>
                        Preferencias
                    </div>
                    <h1 class="mt-4 text-2xl font-extrabold tracking-tight sm:text-3xl">
                        Configuracion de notificaciones
                    </h1>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-blue-50">
                        Define canales, horarios y alcance de avisos para mantener control operativo sin saturar tu bandeja.
                    </p>
                </div>

                <div class="responsive-actions lg:justify-end">
                    <a href="{{ route('profile.edit') }}" class="responsive-action responsive-action--on-dark">
                        <i class="fas fa-user"></i>
                        Perfil
                    </a>
                    <a href="{{ route('plan-accion.index', ['tipo' => 'lavadora']) }}" class="responsive-action responsive-action--on-dark">
                        <i class="fas fa-clipboard-check"></i>
                        Plan de accion
                    </a>
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="alert">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800" role="alert">
            <i class="fas fa-triangle-exclamation mr-2"></i>
            {{ session('warning') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <p class="font-bold">Revisa los campos marcados antes de guardar.</p>
        </div>
    @endif

    <form action="{{ route('notificaciones.configuracion.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70 sm:p-6">
            <header class="flex items-start gap-4 border-b border-slate-100 pb-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                    <i class="fas fa-broadcast-tower"></i>
                </span>
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Canales</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Activa los medios por los que deseas recibir alertas del sistema.
                    </p>
                </div>
            </header>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4 transition duration-200 hover:border-blue-200 hover:bg-blue-50/60">
                    <span class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-blue-700 shadow-sm">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Correo</span>
                            <span class="text-xs font-medium text-slate-500">{{ $user->email }}</span>
                        </span>
                    </span>
                    <input type="checkbox" name="email_notifications" id="email_notifications" class="notification-switch" {{ $settings->email_notifications ? 'checked' : '' }} value="1">
                </label>

                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4 transition duration-200 hover:border-blue-200 hover:bg-blue-50/60">
                    <span class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-blue-700 shadow-sm">
                            <i class="fas fa-sms"></i>
                        </span>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">SMS</span>
                            <span class="text-xs font-medium text-slate-500">Telefono movil</span>
                        </span>
                    </span>
                    <input type="checkbox" name="sms_notifications" id="sms_notifications" class="notification-switch" {{ $settings->sms_notifications ? 'checked' : '' }} value="1">
                </label>

                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4 transition duration-200 hover:border-emerald-200 hover:bg-emerald-50/60">
                    <span class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-emerald-700 shadow-sm">
                            <i class="fab fa-whatsapp"></i>
                        </span>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">WhatsApp</span>
                            <span class="text-xs font-medium text-slate-500">Avisos directos</span>
                        </span>
                    </span>
                    <input type="checkbox" name="whatsapp_notifications" id="whatsapp_notifications" class="notification-switch notification-switch--green" {{ $settings->whatsapp_notifications ? 'checked' : '' }} value="1">
                </label>

                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4 transition duration-200 hover:border-sky-200 hover:bg-sky-50/60">
                    <span class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-sky-700 shadow-sm">
                            <i class="fab fa-telegram"></i>
                        </span>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Telegram</span>
                            <span class="text-xs font-medium text-slate-500">Usuario externo</span>
                        </span>
                    </span>
                    <input type="checkbox" name="telegram_notifications" id="telegram_notifications" class="notification-switch notification-switch--sky" {{ $settings->telegram_notifications ? 'checked' : '' }} value="1">
                </label>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70 sm:p-6">
            <header class="flex items-start gap-4 border-b border-slate-100 pb-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                    <i class="fas fa-address-card"></i>
                </span>
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Datos de contacto</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Mantiene separados los datos de cuenta y los datos usados para avisos.
                    </p>
                </div>
            </header>

            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <div class="lg:col-span-2">
                    <label for="notification_email" class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                        <i class="fas fa-envelope text-blue-600"></i>
                        Correo para notificaciones
                    </label>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input
                            id="notification_email"
                            type="email"
                            name="notification_email"
                            value="{{ old('notification_email', $settings->notification_email ?? $user->email) }}"
                            class="min-w-0 flex-1 rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 text-sm shadow-sm transition duration-200 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100 disabled:bg-slate-100 disabled:text-slate-400"
                            placeholder="{{ $user->email }}"
                            {{ $settings->email_notifications ? '' : 'disabled' }}
                        >
                        <span id="email_status_badge" class="inline-flex items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-bold {{ $settings->email_notifications ? 'border-blue-100 bg-blue-50 text-blue-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
                            <i class="fas {{ $settings->email_notifications ? 'fa-check-circle' : 'fa-circle-xmark' }}"></i>
                            {{ $settings->email_notifications ? 'Activo' : 'Desactivado' }}
                        </span>
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('notification_email')" />
                </div>

                <div class="sms-field {{ $settings->sms_notifications ? '' : 'hidden' }}">
                    <label for="phone_number" class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                        <i class="fas fa-sms text-blue-600"></i>
                        Telefono para SMS
                    </label>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input
                            id="phone_number"
                            type="text"
                            name="phone_number"
                            value="{{ old('phone_number', $settings->phone_number) }}"
                            class="min-w-0 flex-1 rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 text-sm shadow-sm transition duration-200 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                            placeholder="+52 498 109 6696"
                        >
                        @if($settings->phone_number && !$settings->phone_verified)
                            <button type="button" onclick="verificarTelefono()" class="responsive-action responsive-action--compact bg-amber-500">
                                <i class="fas fa-shield"></i>
                                Verificar
                            </button>
                        @elseif($settings->phone_verified)
                            <span class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
                                <i class="fas fa-check-circle"></i>
                                Verificado
                            </span>
                        @endif
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('phone_number')" />
                </div>

                <div class="whatsapp-field {{ $settings->whatsapp_notifications ? '' : 'hidden' }}">
                    <label for="whatsapp_number" class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                        <i class="fab fa-whatsapp text-emerald-600"></i>
                        Numero de WhatsApp
                    </label>
                    <input
                        id="whatsapp_number"
                        type="text"
                        name="whatsapp_number"
                        value="{{ old('whatsapp_number', $settings->whatsapp_number) }}"
                        class="block w-full rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 text-sm shadow-sm transition duration-200 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100"
                        placeholder="+52 498 109 6696"
                    >
                    <x-input-error class="mt-2" :messages="$errors->get('whatsapp_number')" />
                </div>

                <div class="telegram-field {{ $settings->telegram_notifications ? '' : 'hidden' }}">
                    <label for="telegram_user" class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                        <i class="fab fa-telegram text-sky-600"></i>
                        Usuario de Telegram
                    </label>
                    <input
                        id="telegram_user"
                        type="text"
                        name="telegram_user"
                        value="{{ old('telegram_user', $settings->telegram_user) }}"
                        class="block w-full rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 text-sm shadow-sm transition duration-200 focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"
                        placeholder="@usuario"
                    >
                    <x-input-error class="mt-2" :messages="$errors->get('telegram_user')" />
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70 sm:p-6">
            <header class="flex items-start gap-4 border-b border-slate-100 pb-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                    <i class="fas fa-clock"></i>
                </span>
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Tiempo y anticipacion</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Controla cuando se disparan los recordatorios operativos.
                    </p>
                </div>
            </header>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <label for="days_before_notification" class="mb-2 block text-sm font-bold text-slate-700">
                        Dias de anticipacion
                    </label>
                    <select
                        id="days_before_notification"
                        name="days_before_notification"
                        class="block w-full rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 text-sm shadow-sm transition duration-200 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                    >
                        @for($i = 1; $i <= 15; $i++)
                            <option value="{{ $i }}" {{ (int) $settings->days_before_notification === $i ? 'selected' : '' }}>
                                {{ $i }} dia{{ $i !== 1 ? 's' : '' }}
                            </option>
                        @endfor
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('days_before_notification')" />
                </div>

                <div>
                    <label for="notify_at_time" class="mb-2 block text-sm font-bold text-slate-700">
                        Hora de notificacion
                    </label>
                    <input
                        id="notify_at_time"
                        type="time"
                        name="notify_at_time"
                        value="{{ old('notify_at_time', $settings->notify_at_time) }}"
                        class="block w-full rounded-xl border-slate-200 bg-slate-50/80 px-4 py-3 text-sm shadow-sm transition duration-200 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
                    >
                    <x-input-error class="mt-2" :messages="$errors->get('notify_at_time')" />
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70 sm:p-6">
            <header class="flex items-start gap-4 border-b border-slate-100 pb-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                    <i class="fas fa-filter"></i>
                </span>
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Alcance</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Selecciona los tipos de mantenimiento y lineas que deben generar avisos.
                    </p>
                </div>
            </header>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach(['notify_for_pcm1' => 'PCM 1', 'notify_for_pcm2' => 'PCM 2', 'notify_for_pcm3' => 'PCM 3', 'notify_for_pcm4' => 'PCM 4'] as $field => $label)
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 transition duration-200 hover:border-blue-200 hover:bg-blue-50/60">
                        <span class="flex items-center gap-2 text-sm font-bold text-slate-800">
                            <i class="fas fa-screwdriver-wrench text-blue-600"></i>
                            {{ $label }}
                        </span>
                        <input type="checkbox" name="{{ $field }}" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" {{ $settings->{$field} ? 'checked' : '' }}>
                    </label>
                @endforeach
            </div>

            <div class="mt-6 border-t border-slate-100 pt-5">
                <label class="flex cursor-pointer flex-col gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4 transition duration-200 hover:border-blue-200 hover:bg-blue-50/60 sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        <span class="flex items-center gap-2 text-sm font-bold text-slate-900">
                            <i class="fas fa-route text-blue-600"></i>
                            Solo lineas especificas
                        </span>
                        <span class="mt-1 block text-sm text-slate-500">Limita las alertas a las lineas seleccionadas.</span>
                    </span>
                    <input type="checkbox" name="notify_only_my_lines" id="notify_only_my_lines" value="1" class="notification-switch" {{ $settings->notify_only_my_lines ? 'checked' : '' }}>
                </label>

                <div id="lines_selection" class="{{ $settings->notify_only_my_lines ? '' : 'hidden' }} mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4">
                    <p class="mb-3 text-sm font-semibold text-slate-600">Selecciona las lineas que te interesan:</p>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach($lineas as $linea)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50">
                                <input
                                    type="checkbox"
                                    name="lines_to_notify[]"
                                    value="{{ $linea->id }}"
                                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    {{ in_array((int) $linea->id, $selectedLines, true) ? 'checked' : '' }}
                                >
                                <span>{{ $linea->nombre_completo }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70 sm:p-6">
            <header class="flex items-start gap-4 border-b border-slate-100 pb-5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                    <i class="fas fa-sliders"></i>
                </span>
                <div>
                    <h2 class="text-xl font-extrabold text-slate-900">Preferencias adicionales</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Ajusta el comportamiento fino de resumen y prioridad.
                    </p>
                </div>
            </header>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                @foreach([
                    'urgent_only' => ['label' => 'Solo urgentes', 'copy' => 'Notifica eventos con menos de 24 horas.'],
                    'include_weekends' => ['label' => 'Incluir fines de semana', 'copy' => 'Mantiene avisos activos sabado y domingo.'],
                    'summary_daily' => ['label' => 'Resumen diario', 'copy' => 'Agrupa actividad diaria relevante.'],
                    'summary_weekly' => ['label' => 'Resumen semanal', 'copy' => 'Envia una vista general por semana.'],
                ] as $field => $option)
                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4 transition duration-200 hover:border-blue-200 hover:bg-blue-50/60">
                        <span>
                            <span class="block text-sm font-bold text-slate-900">{{ $option['label'] }}</span>
                            <span class="text-xs font-medium text-slate-500">{{ $option['copy'] }}</span>
                        </span>
                        <input type="checkbox" name="{{ $field }}" value="1" class="notification-switch" {{ ($preferences[$field] ?? in_array($field, ['include_weekends', 'summary_daily'], true)) ? 'checked' : '' }}>
                    </label>
                @endforeach
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <p class="text-sm font-medium text-slate-500">
                Los cambios se aplicaran al siguiente ciclo de notificaciones.
            </p>

            <div class="responsive-actions sm:justify-end">
                <a href="{{ route('profile.edit') }}" class="responsive-action responsive-action--secondary">
                    <i class="fas fa-arrow-left"></i>
                    Cancelar
                </a>
                <button type="submit" class="responsive-action">
                    <i class="fas fa-floppy-disk"></i>
                    Guardar configuracion
                </button>
            </div>
        </div>
    </form>
</div>

<style>
    .notification-switch {
        appearance: none;
        position: relative;
        width: 3rem;
        height: 1.55rem;
        flex: 0 0 auto;
        cursor: pointer;
        border-radius: 999px;
        background: #cbd5e1;
        transition: background-color 0.2s ease, box-shadow 0.2s ease;
    }

    .notification-switch::before {
        content: '';
        position: absolute;
        top: 0.18rem;
        left: 0.2rem;
        width: 1.18rem;
        height: 1.18rem;
        border-radius: 999px;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.18);
        transition: transform 0.2s ease;
    }

    .notification-switch:checked {
        background: #2563eb;
    }

    .notification-switch--green:checked {
        background: #059669;
    }

    .notification-switch--sky:checked {
        background: #0284c7;
    }

    .notification-switch:checked::before {
        transform: translateX(1.42rem);
    }

    .notification-switch:focus-visible {
        outline: 3px solid rgba(59, 130, 246, 0.35);
        outline-offset: 3px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const smsCheckbox = document.getElementById('sms_notifications');
    const whatsappCheckbox = document.getElementById('whatsapp_notifications');
    const telegramCheckbox = document.getElementById('telegram_notifications');
    const emailCheckbox = document.getElementById('email_notifications');
    const emailField = document.getElementById('notification_email');
    const emailBadge = document.getElementById('email_status_badge');
    const onlyMyLines = document.getElementById('notify_only_my_lines');
    const linesSelection = document.getElementById('lines_selection');

    function toggleVisibility(selector, visible) {
        document.querySelectorAll(selector).forEach(function(element) {
            element.classList.toggle('hidden', !visible);
        });
    }

    function updateEmailState() {
        if (!emailCheckbox || !emailField) {
            return;
        }

        emailField.disabled = !emailCheckbox.checked;

        if (!emailBadge) {
            return;
        }

        emailBadge.className = 'inline-flex items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-bold ' + (
            emailCheckbox.checked
                ? 'border-blue-100 bg-blue-50 text-blue-700'
                : 'border-slate-200 bg-slate-50 text-slate-500'
        );
        emailBadge.innerHTML = emailCheckbox.checked
            ? '<i class="fas fa-check-circle"></i> Activo'
            : '<i class="fas fa-circle-xmark"></i> Desactivado';
    }

    emailCheckbox?.addEventListener('change', updateEmailState);
    smsCheckbox?.addEventListener('change', function() {
        toggleVisibility('.sms-field', this.checked);
    });
    whatsappCheckbox?.addEventListener('change', function() {
        toggleVisibility('.whatsapp-field', this.checked);
    });
    telegramCheckbox?.addEventListener('change', function() {
        toggleVisibility('.telegram-field', this.checked);
    });
    onlyMyLines?.addEventListener('change', function() {
        linesSelection?.classList.toggle('hidden', !this.checked);
    });

    updateEmailState();
});

function verificarTelefono() {
    const code = prompt('Ingresa el codigo de verificacion enviado a tu telefono:');

    if (!code) {
        return;
    }

    fetch('{{ route("notificaciones.verify.phone") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ code: code })
    })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                alert('Telefono verificado correctamente');
                location.reload();
                return;
            }

            alert(data.message || 'Codigo incorrecto');
        });
}
</script>
@endsection
