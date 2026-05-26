@extends('layouts.app')

@section('title', 'Editar analisis')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Editar analisis</h1>
        <p class="text-sm text-gray-500">Actualiza la informacion del registro.</p>
    </div>

    @include('analisis._form', [
        'action' => route('analisis.update', $analisis),
        'formMethod' => 'PUT',
    ])
</div>
@endsection
