import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

// DOM mínimo para probar los selectores reales sin añadir dependencias al proyecto.
class Element {
    value = '';
    children = [];
    listeners = {};
    classList = { add() {}, remove() {} };
    set innerHTML(value) { this.html = value; this.children = []; }
    get innerHTML() { return this.html || ''; }
    appendChild(child) { this.children.push(child); }
    addEventListener(name, callback) { this.listeners[name] = callback; }
    emit(name) { this.listeners[name]?.(); }
    querySelectorAll(selector) {
        const inputs = this.children.map((child) => {
            const value = child.innerHTML.match(/(?:data-component-value|value)="(\d+)"/)?.[1];
            return { value, dataset: { componentValue: value }, checked: child.innerHTML.includes('checked'), addEventListener() {} };
        });
        return selector.includes(':checked') ? inputs.filter((input) => input.checked) : inputs;
    }
}

function formulario(nombre) {
    const source = readFileSync(new URL(`../../resources/views/pasteurizadora/analisis-pasteurizadora/${nombre}.blade.php`, import.meta.url), 'utf8');
    const start = source.indexOf('const componentesConfiguracion =');
    const end = source.indexOf('const evidenciaFotosInput =', start);
    const configuracion = [
        { codigo: 'ANILLAS', nombre: 'Anillas', cantidad: 5, es_brazo_torsion: false, numeros_por_modulo: { 2: [6, 7, 8, 9, 10], 16: [76, 77, 78, 79, 80] } },
        { codigo: 'VIGAS_FIJAS', nombre: 'Viga fija', cantidad: 1, es_brazo_torsion: false, numeros_por_modulo: { 2: [2], 16: [16] } },
    ];
    const elements = new Map();
    const document = {
        getElementById(id) {
            if (!elements.has(id)) elements.set(id, new Element());
            return elements.get(id);
        },
        createElement() { return new Element(); },
    };
    document.getElementById('modulo').value = '2';
    document.getElementById('componente').value = 'ANILLAS';
    const replacements = [JSON.stringify(configuracion), ...(nombre === 'create-quick' ? ['{}'] : []), '[6]'];
    let index = 0;
    let script = source.slice(start, end).replace(/@json\([^\n]+\)/g, () => replacements[index++]);
    if (nombre === 'create') {
        script += '\n' + source.split('\n').find((line) => line.startsWith("moduloSelect.addEventListener('change'"));
    }
    const context = { document };
    runInNewContext(script, context);
    return {
        elements,
        render() { runInNewContext(nombre === 'create' ? 'renderChecklistComponentes()' : 'renderChecklist()', context); },
        values() { return document.getElementById('componentes-checklist').querySelectorAll('input').map((input) => Number(input.value)); },
        selection() { return JSON.parse(document.getElementById('componentes_revisados_input').value); },
    };
}

for (const nombre of ['create', 'create-quick']) {
    test(`${nombre}: al cambiar de módulo se muestran los números globales y se limpia la selección anterior`, () => {
        const form = formulario(nombre);
        form.render();
        assert.deepEqual(form.values(), [6, 7, 8, 9, 10]);
        assert.deepEqual(form.selection(), [6]);
        form.elements.get('modulo').value = '16';
        form.elements.get('modulo').emit('change');
        assert.deepEqual(form.values(), [76, 77, 78, 79, 80]);
        assert.deepEqual(form.selection(), []);
    });

    test(`${nombre}: una pieza única conserva el número de su módulo`, () => {
        const form = formulario(nombre);
        form.elements.get('componente').value = 'VIGAS_FIJAS';
        form.render();
        assert.deepEqual(form.selection(), [2]);
        form.elements.get('modulo').value = '16';
        form.elements.get('modulo').emit('change');
        assert.deepEqual(form.selection(), [16]);
    });
}
