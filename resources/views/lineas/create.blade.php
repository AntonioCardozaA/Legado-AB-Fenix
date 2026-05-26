@extends('layouts.app')

@section('title', 'Nueva linea')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Nueva linea</h1>
        <p class="text-sm text-gray-500">Registra una nueva linea operativa.</p>
    </div>

    @include('lineas._form', [
        'action' => route('lineas.store'),
        'method' => 'POST',
        'linea' => null,
    ])
</div>
@endsection
