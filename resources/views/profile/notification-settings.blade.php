{{-- resources/views/profile/notification-settings.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700">
                <h2 class="text-xl font-semibold text-white flex items-center">
                    <i class="fas fa-bell mr-2"></i>
                    Configuración de Notificaciones
                </h2>
            </div>

            <form action="{{ route('profile.notifications.update') }}" method="POST" class="p-6">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    {{-- Notificaciones por Email --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="flex items-center justify-between cursor-pointer">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-envelope text-2xl text-blue-600"></i>
                                <div>
                                    <span class="font-medium text-gray-900">Notificaciones por Email</span>
                                    <p class="text-sm text-gray-600">Recibe alertas en tu correo electrónico</p>
                                </div>
                            </div>
                            <div class="relative">
                                <input type="checkbox" 
                                       name="email_notifications" 
                                       class="toggle-checkbox"
                                       {{ $settings->email_notifications ? 'checked' : '' }}
                                       value="1">
                            </div>
                        </label>
                    </div>

                    {{-- Notificaciones por SMS --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="flex items-center justify-between cursor-pointer">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-mobile-alt text-2xl text-green-600"></i>
                                <div>
                                    <span class="font-medium text-gray-900">Notificaciones por SMS</span>
                                    <p class="text-sm text-gray-600">Recibe alertas en tu teléfono móvil</p>
                                </div>
                            </div>
                            <div class="relative">
                                <input type="checkbox" 
                                       name="sms_notifications" 
                                       class="toggle-checkbox sms-toggle"
                                       {{ $settings->sms_notifications ? 'checked' : '' }}
                                       value="1">
                            </div>
                        </label>

                        {{-- Campo de teléfono (visible solo si SMS está activado) --}}
                        <div class="sms-phone-field mt-4 {{ $settings->sms_notifications ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Número de teléfono
                            </label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500">
                                    +57
                                </span>
                                <input type="tel" 
                                       name="phone_number" 
                                       value="{{ $settings->phone_number }}"
                                       class="flex-1 rounded-none rounded-r-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="3001234567">
                            </div>
                            <p class="mt-2 text-xs text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                Ingresa tu número sin espacios ni guiones
                            </p>
                        </div>
                    </div>

                    {{-- Días de anticipación --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block">
                            <div class="flex items-center space-x-3 mb-3">
                                <i class="fas fa-calendar-alt text-2xl text-purple-600"></i>
                                <span class="font-medium text-gray-900">Notificar con anticipación</span>
                            </div>
                            <select name="days_before_notification" 
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="1" {{ $settings->days_before_notification == 1 ? 'selected' : '' }}>1 día antes</option>
                                <option value="2" {{ $settings->days_before_notification == 2 ? 'selected' : '' }}>2 días antes</option>
                                <option value="3" {{ $settings->days_before_notification == 3 ? 'selected' : '' }}>3 días antes</option>
                                <option value="5" {{ $settings->days_before_notification == 5 ? 'selected' : '' }}>5 días antes</option>
                                <option value="7" {{ $settings->days_before_notification == 7 ? 'selected' : '' }}>7 días antes</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end space-x-3">
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                        Guardar Configuración
                    </button>
                </div>
            </form>
        </div>

        {{-- Historial de notificaciones --}}
        <div class="mt-8 bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Últimas Notificaciones</h3>
            </div>
            <div class="divide-y">
                @forelse(auth()->user()->notifications()->take(5)->get() as $notification)
                <div class="p-4 {{ $notification->read_at ? 'bg-white' : 'bg-blue-50' }}">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-800">{!! $notification->data['mensaje'] ?? $notification->data['message'] ?? '' !!}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        @if(!$notification->read_at)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Nueva
                        </span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="p-4 text-center text-gray-500">
                    No tienes notificaciones
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<style>
.toggle-checkbox {
    appearance: none;
    width: 50px;
    height: 24px;
    background-color: #e5e7eb;
    border-radius: 9999px;
    position: relative;
    cursor: pointer;
    transition: all 0.2s;
}

.toggle-checkbox:checked {
    background-color: #3b82f6;
}

.toggle-checkbox::before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    background-color: white;
    border-radius: 9999px;
    top: 2px;
    left: 2px;
    transition: all 0.2s;
}

.toggle-checkbox:checked::before {
    left: 28px;
}

.hidden {
    display: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const smsToggle = document.querySelector('.sms-toggle');
    const smsField = document.querySelector('.sms-phone-field');
    
    if (smsToggle) {
        smsToggle.addEventListener('change', function() {
            if (this.checked) {
                smsField.classList.remove('hidden');
            } else {
                smsField.classList.add('hidden');
            }
        });
    }
});
</script>
@endsection