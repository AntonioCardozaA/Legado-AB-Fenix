@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Centro de notificaciones</h1>
                <p class="text-sm text-gray-500">Consulta alertas internas y marca su lectura cuando lo necesites.</p>
            </div>

            @if(auth()->user()->unreadNotifications()->count() > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                        <i class="fas fa-check-double"></i>
                        Marcar todas como leidas
                    </button>
                </form>
            @endif
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($notifications as $notification)
                <div class="px-6 py-5 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50/60' }}">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                @php
                                    $priority = $notification->data['prioridad'] ?? 'baja';
                                @endphp

                                @if($priority === 'alta')
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-red-100 text-red-600">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </span>
                                @elseif($priority === 'media')
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-yellow-100 text-yellow-600">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </span>
                                @else
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <i class="fas fa-info-circle"></i>
                                    </span>
                                @endif

                                <div>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $notification->data['title'] ?? 'Notificacion interna' }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                            </div>

                            <p class="text-sm text-gray-700 leading-relaxed">
                                {{ $notification->data['message'] ?? $notification->data['mensaje'] ?? 'Nueva notificacion.' }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            @if(!empty($notification->data['url']))
                                <a
                                    href="{{ $notification->data['url'] }}"
                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                                    <i class="fas fa-arrow-up-right-from-square"></i>
                                    Abrir
                                </a>
                            @endif

                            @if(!$notification->read_at)
                                <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200 transition">
                                        <i class="fas fa-check"></i>
                                        Marcar como leida
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center text-gray-500">
                    <i class="fas fa-bell-slash text-3xl mb-3 text-gray-300"></i>
                    <p class="text-sm">No hay notificaciones registradas.</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
