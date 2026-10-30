@extends('layouts.app')

@section('title', 'Destinatarios de alertas')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="rounded-3xl bg-gradient-to-br from-slate-950 via-blue-950 to-blue-700 px-5 py-7 text-white shadow-lg sm:px-8">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-blue-200">
                    <i class="fas fa-shield-halved"></i>
                    Administración
                </div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Destinatarios de alertas</h1>
                <p class="mt-3 max-w-3xl text-sm leading-7 text-blue-100">
                    Guarda los teléfonos que recibirán alertas del sistema y define qué tipos de avisos debe recibir cada uno.
                    La configuración se reutiliza automáticamente en los próximos envíos.
                </p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm text-blue-50">
                <i class="fas fa-lock mr-2"></i>Solo administradores
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <p class="font-bold">Revisa los datos del destinatario.</p>
            <ul class="mt-2 list-disc pl-5 text-xs">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex items-start gap-4 border-b border-slate-100 pb-5">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                <i class="fas fa-user-plus"></i>
            </span>
            <div>
                <h2 class="text-xl font-extrabold text-slate-900">Agregar teléfono</h2>
                <p class="mt-1 text-sm text-slate-600">Los datos guardados aquí aplicarán a las alertas futuras que selecciones.</p>
            </div>
        </div>

        <form action="{{ route('admin.alert-recipients.store') }}" method="POST" class="mt-5 space-y-5">
            @csrf
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="name" class="mb-2 block text-sm font-bold text-slate-700">Nombre o referencia <span class="font-normal text-slate-400">(opcional)</span></label>
                    <input id="name" name="name" value="{{ old('name') }}" class="w-full rounded-xl border-slate-200 bg-slate-50/70 px-4 py-3 text-sm focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100" placeholder="Ej. Supervisor de mantenimiento">
                </div>
                <div>
                    <label for="phone_number" class="mb-2 block text-sm font-bold text-slate-700">Número de teléfono</label>
                    <input id="phone_number" name="phone_number" value="{{ old('phone_number') }}" required class="w-full rounded-xl border-slate-200 bg-slate-50/70 px-4 py-3 text-sm focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100" placeholder="+52 498 123 4567">
                    <p class="mt-1 text-xs text-slate-500">Usa código de país cuando sea necesario.</p>
                </div>
                <div>
                    <label for="channel" class="mb-2 block text-sm font-bold text-slate-700">Canal</label>
                    <select id="channel" name="channel" class="w-full rounded-xl border-slate-200 bg-slate-50/70 px-4 py-3 text-sm focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100">
                        @foreach($channels as $value => $label)
                            <option value="{{ $value }}" @selected(old('channel', 'whatsapp') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <p class="mb-3 text-sm font-bold text-slate-700">Tipos de alerta</p>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($alertTypes as $value => $label)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-3 text-sm transition hover:border-blue-200 hover:bg-blue-50/50">
                            <input type="checkbox" name="alert_types[]" value="{{ $value }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(in_array($value, old('alert_types', ['all']), true))>
                            <span class="font-semibold text-slate-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-slate-500">“Todas las alertas” incluye las nuevas categorías que se agreguen después.</p>
            </div>

            <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <label class="inline-flex items-center gap-3 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="active" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    Destinatario activo para próximas alertas
                </label>
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
                    <i class="fas fa-save"></i> Guardar destinatario
                </button>
            </div>
        </form>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <h2 class="text-xl font-extrabold text-slate-900">Teléfonos configurados</h2>
                <p class="mt-1 text-sm text-slate-600">Puedes desactivar temporalmente un número sin eliminarlo.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $recipients->count() }} guardados</span>
        </div>

        <div class="mt-5 space-y-4">
            @forelse($recipients as $recipient)
                <form action="{{ route('admin.alert-recipients.update', $recipient) }}" method="POST" class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 sm:p-5">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-4 xl:grid-cols-[1fr,1fr,180px]">
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Referencia</label>
                            <input name="name" value="{{ $recipient->name }}" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-100">
                            <label class="mb-2 mt-3 block text-xs font-bold uppercase tracking-wide text-slate-500">Teléfono</label>
                            <input name="phone_number" value="{{ $recipient->phone_number }}" required class="w-full rounded-xl border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Alertas</label>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach($alertTypes as $value => $label)
                                    <label class="flex items-start gap-2 text-xs font-semibold text-slate-700">
                                        <input type="checkbox" name="alert_types[]" value="{{ $value }}" class="mt-0.5 rounded border-slate-300 text-blue-600" @checked($recipient->receivesAlertType($value) && (in_array($value, $recipient->alert_types ?? [], true) || in_array('all', $recipient->alert_types ?? [], true)))>
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500">Canal</label>
                            <select name="channel" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2.5 text-sm">
                                @foreach($channels as $value => $label)
                                    <option value="{{ $value }}" @selected($recipient->channel === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <label class="mt-4 flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input type="checkbox" name="active" value="1" class="rounded border-slate-300 text-emerald-600" @checked($recipient->active)>
                                Activo
                            </label>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-bold text-white hover:bg-blue-700"><i class="fas fa-save mr-1"></i>Actualizar</button>
                                <button type="submit" form="delete-recipient-{{ $recipient->id }}" class="rounded-xl border border-red-200 bg-white px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-50"><i class="fas fa-trash mr-1"></i>Eliminar</button>
                            </div>
                        </div>
                    </div>
                </form>
                <form id="delete-recipient-{{ $recipient->id }}" action="{{ route('admin.alert-recipients.destroy', $recipient) }}" method="POST" onsubmit="return confirm('¿Eliminar este teléfono de las alertas futuras?');">
                    @csrf
                    @method('DELETE')
                </form>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 px-5 py-10 text-center text-sm text-slate-500">
                    <i class="fas fa-mobile-screen-button mb-3 text-2xl text-slate-300"></i>
                    <p class="font-semibold">Aún no hay teléfonos guardados.</p>
                    <p class="mt-1">Agrega el primero arriba para comenzar a enviar alertas externas.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
