{{-- Partial para cargar componentes dinámicamente según la línea seleccionada --}}
<div class="componentes-selector">
    <label for="componente" class="block text-sm font-medium text-gray-700 mb-1">
        <i class="fas fa-cog text-blue-600 mr-1"></i>
        Componente *
    </label>
    <select name="componente" id="componente" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
        <option value="">Primero seleccione una línea...</option>
    </select>
    <p class="text-xs text-gray-500 mt-1" id="componente-info"></p>
</div>

<script>
function cargarComponentes(lineaId, componenteSeleccionado = null) {
    if (!lineaId) {
        document.getElementById('componente').innerHTML = '<option value="">Primero seleccione una línea...</option>';
        return;
    }
    
    fetch(`/analisis-pasteurizadora/ajax/componentes?linea_id=${lineaId}`)
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('componente');
            select.innerHTML = '<option value="">Seleccionar componente...</option>';
            
            Object.entries(data).forEach(([codigo, compData]) => {
                const option = document.createElement('option');
                option.value = codigo;
                const nombre = compData.nombre || compData;
                const cantidad = Number(compData.cantidad || 0);
                option.textContent = cantidad > 0 ? `${nombre} (${cantidad} und)` : nombre;
                if (componenteSeleccionado && componenteSeleccionado == codigo) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            
            document.getElementById('componente-info').innerHTML = `Total de componentes: ${Object.keys(data).length}`;
        });
}

document.getElementById('linea_id')?.addEventListener('change', function() {
    cargarComponentes(this.value);
});
</script>
