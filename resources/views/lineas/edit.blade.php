@extends('layouts.app')

@section('title', 'Editar linea')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Editar {{ $linea->nombre }}</h1>
        <p class="text-sm text-gray-500">Actualiza la informacion de la linea.</p>
    </div>

    @include('lineas._form', [
        'action' => route('lineas.update', $linea),
        'method' => 'PUT',
    ])
</div>
@endsection
