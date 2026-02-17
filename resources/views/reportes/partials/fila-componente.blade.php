<tr>
    <td>
        <div class="font-medium text-gray-800">{{ $item['componente'] ?? 'Sin nombre' }}</div>
    </td>

    <td>
        <span class="text-xs bg-gray-100 px-2 py-1 rounded">
            {{ $item['codigo'] ?? 'N/A' }}
        </span>
    </td>

    <td>
        <span class="font-medium">{{ $item['total'] ?? 0 }}</span> /
        <span class="{{ ($item['revisado'] ?? 0) < ($item['total'] ?? 0) ? 'text-yellow-600' : 'text-green-600' }}">
            {{ $item['revisado'] ?? 0 }}
        </span>
    </td>

    <td><span class="badge bueno">{{ $item['estados']['BUENO'] ?? 0 }}</span></td>
    <td><span class="badge regular">{{ $item['estados']['DESGASTE_MODERADO'] ?? 0 }}</span></td>
    <td><span class="badge regular">{{ $item['estados']['DESGASTE_SEVERO'] ?? 0 }}</span></td>
    <td><span class="badge danado">{{ $item['estados']['DANADO_REQUIERE'] ?? 0 }}</span></td>
    <td><span class="badge reemplazado">{{ $item['estados']['DANADO_CAMBIADO'] ?? 0 }}</span></td>

    <td>
        <div class="flex items-center gap-2">
            <div class="progress-bar w-20">
                <div class="progress-bar-fill 
                    {{ ($item['porcentaje_revisado'] ?? 0) < 50 ? 'red' : 
                       (($item['porcentaje_revisado'] ?? 0) < 80 ? 'yellow' : 'green') }}" 
                    style="width: {{ $item['porcentaje_revisado'] ?? 0 }}%">
                </div>
            </div>
            <span class="text-sm font-medium">
                {{ number_format($item['porcentaje_revisado'] ?? 0, 1) }}%
            </span>
        </div>
    </td>

    <td>
        <a href="{{ route('analisis-lavadora.index', ['componente_id' => $item['componente_id'] ?? '']) }}"
           class="text-blue-600 hover:text-blue-800 text-sm">
            <i class="fas fa-eye"></i>
        </a>
    </td>
</tr>
