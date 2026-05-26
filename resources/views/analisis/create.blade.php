@extends('layouts.app')

@section('title', 'Nuevo analisis')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Nuevo analisis</h1>
        <p class="text-sm text-gray-500">Captura la informacion del componente seleccionado.</p>
    </div>

    @include('analisis._form', [
        'action' => route('analisis.store'),
        'formMethod' => 'POST',
        'analisis' => null,
    ])
</div>
@endsection
