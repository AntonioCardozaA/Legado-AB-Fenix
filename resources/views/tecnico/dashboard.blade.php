@extends('layouts.app')

@php
    $dashboardRoleLabel = $userRoleLabel ?? auth()->user()?->role_label ?? 'Usuario';
@endphp

@section('title', 'Dashboard ' . $dashboardRoleLabel)

@section('content')
<div class="relative min-h-screen w-full bg-cover bg-center bg-no-repeat bg-fixed" 
     style="background-image: url('{{ asset('images/fondo.png') }}');">
    
    {{-- Overlay oscuro para legibilidad --}}
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    
    {{-- Contenido centrado - Totalmente responsive --}}
    <div class="relative z-10 flex items-center justify-center min-h-screen px-4 sm:px-6 md:px-8">
        <div class="text-center max-w-full">
            {{-- Título - tamaños progresivos --}}
            <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold text-white mb-3 sm:mb-4 drop-shadow-lg px-2">
                ¡Bienvenido!
            </h1>
            
            {{-- Nombre del técnico --}}
            <p class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-semibold text-blue-300 drop-shadow-lg px-2 break-words">
                {{ auth()->user()->name }}
            </p>
            
            {{-- Rol del usuario autenticado --}}
            <p class="text-base sm:text-lg md:text-xl text-gray-200 mt-4 sm:mt-6 drop-shadow px-2">
             {{ $dashboardRoleLabel }}
            </p>
        </div>
    </div>
</div>
@endsection
