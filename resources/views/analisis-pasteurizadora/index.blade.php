@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Análisis Pasteurizadoras</h1>
            
        </div>
        <div class="flex space-x-3">
       
          
        </div>
    </div>

    <!-- Cards de Componentes - Basado en el Excel -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        <!-- Anillas / Pernos de ojo -->
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Anillas/Pernos</h3>
                    <p class="text-2xl font-bold text-gray-800">12</p>
                    <p class="text-xs text-gray-500">Revisadas: 5</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-link text-blue-600"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Pendiente: 7</span>
            </div>
        </div>

        <!-- Placas perno -->
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Placas Perno</h3>
                    <p class="text-2xl font-bold text-gray-800">12</p>
                    <p class="text-xs text-gray-500">Revisadas: 5</p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-clipboard-check text-green-600"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Pendiente: 7</span>
            </div>
        </div>

        <!-- Parrillas -->
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Parrillas</h3>
                    <p class="text-2xl font-bold text-gray-800">12</p>
                    <p class="text-xs text-gray-500">Revisadas: 5</p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-grip-lines text-purple-600"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Pendiente: 7</span>
            </div>
        </div>

        <!-- Rodamientos -->
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Rodamientos</h3>
                    <p class="text-2xl font-bold text-gray-800">12</p>
                    <p class="text-xs text-gray-500">Revisadas: 5</p>
                </div>
                <div class="bg-orange-100 p-3 rounded-full">
                    <i class="fas fa-cog text-orange-600"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Pendiente: 7</span>
            </div>
        </div>

        <!-- Excéntricos / Levas -->
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Excéntricos/Levas</h3>
                    <p class="text-2xl font-bold text-gray-800">12</p>
                    <p class="text-xs text-gray-500">Revisadas: 5</p>
                </div>
                <div class="bg-red-100 p-3 rounded-full">
                    <i class="fas fa-chart-line text-red-600"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Pendiente: 7</span>
            </div>
        </div>

        <!-- Reglillas -->
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-indigo-500">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Reglillas</h3>
                    <p class="text-2xl font-bold text-gray-800">12</p>
                    <p class="text-xs text-gray-500">Revisadas: 5</p>
                </div>
                <div class="bg-indigo-100 p-3 rounded-full">
                    <i class="fas fa-ruler text-indigo-600"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Pendiente: 7</span>
            </div>
        </div>
    </div>

    <!-- Tabla de Actividades Recientes (Basado en el Excel) -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Actividades Recientes - PAST L-07</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Módulo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Componente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actividad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">6</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Reglillas</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">27/01/2026</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Ajuste, nivelación y enderezado de reglillas</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="#" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-eye"></i></a>
                            <a href="#" class="text-green-600 hover:text-green-900"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">6</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rodamientos</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">15/01/2026</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Cambio de 3 rodamientos de trabes fijas</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="#" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-eye"></i></a>
                            <a href="#" class="text-green-600 hover:text-green-900"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">9</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Placas perno</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">14/01/2026</td>
                        <td class="px-6 py-4 text-sm text-gray-500">Cambio de 7 placas perno y relleno</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="#" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-eye"></i></a>
                            <a href="#" class="text-green-600 hover:text-green-900"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

 

        <!-- Plan de Acción -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Plan de Acción - PCM</h2>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <span class="text-sm font-medium text-gray-700">PCM 1</span>
                        <span class="text-xs bg-gray-200 text-gray-800 px-2 py-1 rounded">Pendiente</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <span class="text-sm font-medium text-gray-700">PCM 2</span>
                        <span class="text-xs bg-gray-200 text-gray-800 px-2 py-1 rounded">Pendiente</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <span class="text-sm font-medium text-gray-700">PCM 3</span>
                        <span class="text-xs bg-gray-200 text-gray-800 px-2 py-1 rounded">Pendiente</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                        <span class="text-sm font-medium text-gray-700">PCM 4</span>
                        <span class="text-xs bg-gray-200 text-gray-800 px-2 py-1 rounded">Pendiente</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection