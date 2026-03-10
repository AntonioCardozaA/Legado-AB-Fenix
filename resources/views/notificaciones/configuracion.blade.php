@extends('layouts.app')

@section('title', 'Configuración de Notificaciones')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <a href="{{ route('plan-accion.index', ['tipo' => 'lavadora']) }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 
                          bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-300 mb-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span class="font-medium">Volver a Plan de Acción</span>
                </a>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                    <i class="fas fa-bell text-blue-600"></i>
                    Configuración de Notificaciones
                </h1>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('warning'))
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-lg" role="alert">
                <p>{{ session('warning') }}</p>
            </div>
        @endif

        <form action="{{ route('notificaciones.configuracion.update') }}" method="POST" class="bg-white rounded-lg shadow-lg overflow-hidden">
            @csrf
            @method('PUT')

            <!-- Canales de Notificación -->
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-broadcast-tower text-blue-600"></i>
                    Canales de Notificación
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Email -->
                    <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                        <input type="checkbox" name="email_notifications" id="email_notifications" 
                               class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500"
                               {{ $settings->email_notifications ? 'checked' : '' }} value="1">
                        <label for="email_notifications" class="ml-3 flex items-center gap-2 cursor-pointer">
                            <i class="fas fa-envelope text-gray-600"></i>
                            <span class="font-medium">Correo Electrónico</span>
                        </label>
                    </div>

                    <!-- SMS -->
                    <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                        <input type="checkbox" name="sms_notifications" id="sms_notifications" 
                               class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500"
                               {{ $settings->sms_notifications ? 'checked' : '' }} value="1">
                        <label for="sms_notifications" class="ml-3 flex items-center gap-2 cursor-pointer">
                            <i class="fas fa-sms text-gray-600"></i>
                            <span class="font-medium">SMS</span>
                        </label>
                    </div>

                    <!-- WhatsApp -->
                    <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                        <input type="checkbox" name="whatsapp_notifications" id="whatsapp_notifications" 
                               class="w-5 h-5 text-green-600 rounded focus:ring-green-500"
                               {{ $settings->whatsapp_notifications ? 'checked' : '' }} value="1">
                        <label for="whatsapp_notifications" class="ml-3 flex items-center gap-2 cursor-pointer">
                            <i class="fab fa-whatsapp text-green-600"></i>
                            <span class="font-medium">WhatsApp</span>
                        </label>
                    </div>

                </div>
            </div>

            <!-- Datos de Contacto -->
            <div class="p-6 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-address-card text-blue-600"></i>
                    Datos de Contacto
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Email (siempre visible pero editable) -->
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-blue-600"></i>
                            Correo electrónico para notificaciones
                        </label>
                        <div class="flex gap-2">
                            <input type="email" name="notification_email" 
                                   value="{{ old('notification_email', $settings->notification_email ?? Auth::user()->email) }}"
                                   class="flex-1 rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="lbrm2307@gmail.com"
                                   {{ $settings->email_notifications ? '' : 'disabled' }}>
                            @if($settings->email_notifications)
                                <span class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-lg">
                                    <i class="fas fa-check-circle mr-2"></i> Activo
                                </span>
                            @else
                                <span class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg">
                                    <i class="fas fa-times-circle mr-2"></i> Desactivado
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Si no se especifica, se usará el email de tu cuenta: {{ Auth::user()->email }}</p>
                    </div>

                    <!-- Teléfono para SMS -->
                    <div class="sms-field" style="{{ $settings->sms_notifications ? '' : 'display: none;' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-sms mr-2 text-blue-600"></i>
                            Número de teléfono para SMS
                        </label>
                        <div class="flex gap-2">
                            <input type="text" name="phone_number" value="{{ old('phone_number', $settings->phone_number) }}"
                                   class="flex-1 rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="+52 498 109 6696">
                            @if($settings->phone_number && !$settings->phone_verified)
                                <button type="button" onclick="verificarTelefono()"
                                        class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                                    Verificar
                                </button>
                            @elseif($settings->phone_verified)
                                <span class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-lg">
                                    <i class="fas fa-check-circle mr-2"></i> Verificado
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- WhatsApp -->
                    <div class="whatsapp-field" style="{{ $settings->whatsapp_notifications ? '' : 'display: none;' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fab fa-whatsapp mr-2 text-green-600"></i>
                            Número de WhatsApp
                        </label>
                        <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $settings->whatsapp_number) }}"
                               class="w-full rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500"
                               placeholder="+52 498 109 6696">
                    </div>
                </div>
            </div>

            <!-- Configuración de Tiempo -->
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-clock text-blue-600"></i>
                    Configuración de Tiempo
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Notificar con anticipación de
                        </label>
                        <select name="days_before_notification" 
                                class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                            @for($i = 1; $i <= 15; $i++)
                                <option value="{{ $i }}" {{ $settings->days_before_notification == $i ? 'selected' : '' }}>
                                    {{ $i }} día{{ $i != 1 ? 's' : '' }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Hora de notificación
                        </label>
                        <input type="time" name="notify_at_time" value="{{ $settings->notify_at_time }}"
                               class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <!-- Qué Notificar -->
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-filter text-blue-600"></i>
                    ¿Qué notificar?
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="notify_for_pcm1" value="1" 
                               {{ $settings->notify_for_pcm1 ? 'checked' : '' }}>
                        <span class="font-medium">PCM 1</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="notify_for_pcm2" value="1" 
                               {{ $settings->notify_for_pcm2 ? 'checked' : '' }}>
                        <span class="font-medium">PCM 2</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="notify_for_pcm3" value="1" 
                               {{ $settings->notify_for_pcm3 ? 'checked' : '' }}>
                        <span class="font-medium">PCM 3</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="notify_for_pcm4" value="1" 
                               {{ $settings->notify_for_pcm4 ? 'checked' : '' }}>
                        <span class="font-medium">PCM 4</span>
                    </label>
                </div>

                <!-- Líneas específicas -->
                <div class="mt-4">
                    <label class="flex items-center gap-2 mb-3">
                        <input type="checkbox" name="notify_only_my_lines" id="notify_only_my_lines" value="1"
                               {{ $settings->notify_only_my_lines ? 'checked' : '' }}>
                        <span class="font-medium">Solo notificarme de líneas específicas</span>
                    </label>

                    <div id="lines_selection" style="{{ $settings->notify_only_my_lines ? '' : 'display: none;' }}" 
                         class="ml-6 p-4 bg-gray-50 rounded-lg">
                        <p class="text-sm text-gray-600 mb-3">Selecciona las líneas que te interesan:</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            @foreach($lineas as $linea)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="lines_to_notify[]" value="{{ $linea->id }}"
                                           {{ in_array($linea->id, $settings->lines_to_notify ?? []) ? 'checked' : '' }}>
                                    <span>{{ $linea->nombre_completo }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preferencias Adicionales -->
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-sliders-h text-blue-600"></i>
                    Preferencias Adicionales
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="urgent_only" value="1"
                               {{ ($settings->preferences['urgent_only'] ?? false) ? 'checked' : '' }}>
                        <span>Solo notificaciones urgentes (menos de 24 horas)</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="include_weekends" value="1"
                               {{ ($settings->preferences['include_weekends'] ?? true) ? 'checked' : '' }}>
                        <span>Incluir fines de semana</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="summary_daily" value="1"
                               {{ ($settings->preferences['summary_daily'] ?? true) ? 'checked' : '' }}>
                        <span>Resumen diario de actividades</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="summary_weekly" value="1"
                               {{ ($settings->preferences['summary_weekly'] ?? false) ? 'checked' : '' }}>
                        <span>Resumen semanal</span>
                    </label>
                </div>
            </div>

            <!-- Botones -->
            <div class="p-6 bg-gray-50 flex justify-end gap-3">
                <a href="{{ route('plan-accion.index', ['tipo' => 'lavadora']) }}" 
                   class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-200">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                    Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar/ocultar campos según canales seleccionados
    const smsCheckbox = document.getElementById('sms_notifications');
    const whatsappCheckbox = document.getElementById('whatsapp_notifications');
    const telegramCheckbox = document.getElementById('telegram_notifications');
    const emailCheckbox = document.getElementById('email_notifications');
    const emailField = document.querySelector('input[name="notification_email"]');
    
    // Email field handler
    emailCheckbox.addEventListener('change', function() {
        emailField.disabled = !this.checked;
        if (this.checked) {
            emailField.classList.remove('bg-gray-100');
        } else {
            emailField.classList.add('bg-gray-100');
        }
    });
    
    smsCheckbox.addEventListener('change', function() {
        const smsField = document.querySelector('.sms-field');
        smsField.style.display = this.checked ? 'block' : 'none';
    });
    
    whatsappCheckbox.addEventListener('change', function() {
        document.querySelector('.whatsapp-field').style.display = this.checked ? 'block' : 'none';
    });
    
    telegramCheckbox.addEventListener('change', function() {
        document.querySelector('.telegram-field').style.display = this.checked ? 'block' : 'none';
    });

    // Mostrar/ocultar selección de líneas
    const onlyMyLines = document.getElementById('notify_only_my_lines');
    const linesSelection = document.getElementById('lines_selection');
    
    onlyMyLines.addEventListener('change', function() {
        linesSelection.style.display = this.checked ? 'block' : 'none';
    });

    // Inicializar estado del email
    if (!emailCheckbox.checked) {
        emailField.disabled = true;
        emailField.classList.add('bg-gray-100');
    }
});

function verificarTelefono() {
    const code = prompt('Ingresa el código de verificación enviado a tu teléfono:');
    if (code) {
        fetch('{{ route("notificaciones.verify.phone") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ code: code })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Teléfono verificado correctamente');
                location.reload();
            } else {
                alert('Código incorrecto');
            }
        });
    }
}
</script>
@endsection