// Compile both variants with identical esbuild options, without altering production files/build.
import assert from 'node:assert/strict';
import { build } from 'esbuild';
import { readFile, writeFile, mkdir, mkdtemp } from 'node:fs/promises';
import { resolve, join } from 'node:path';
import { tmpdir } from 'node:os';

const source = await readFile('resources/js/dashboard-lavadora-panels.js', 'utf8');
const before = source
    .replace("import { createVisibleChartRenderer } from './visible-chart.mjs';\n", '')
    .replace('    const renderVisibleChart = createVisibleChartRenderer();\n', '')
    .replace("        renderVisibleChart.cancel(document.getElementById('historicoChart'));\n", '')
    .replace("        renderVisibleChart(canvas, () => safeRenderSection('historico', () => {\n", '')
    .replace('        }));\n    }\n\n    function handleTrendRenderFailure', '    }\n\n    function handleTrendRenderFailure');
assert.ok(!before.includes('renderVisibleChart') && before.length < source.length);
const manifest = JSON.parse(await readFile('public/build/manifest.json', 'utf8'));
const asset = '/build/' + manifest['resources/js/dashboard-lavadora-panels.js'].file;
const html = await readFile(join(resolve(process.argv[2]), 'dashboard.html'), 'utf8');
assert.ok(html.includes(asset));
const roots = {};
for (const [variant, contents] of [['before', before], ['after', source]]) {
    const root = await mkdtemp(join(tmpdir(), 'legado-browser-'));
    await mkdir(join(root, 'assets'));
    const built = await build({ stdin: { contents, resolveDir: resolve('resources/js'), loader: 'js' }, bundle: true, minify: true, format: 'esm', write: false });
    await writeFile(join(root, 'assets/panels.js'), built.outputFiles[0].contents);
    await writeFile(join(root, 'dashboard.html'), html.replaceAll(asset, '/_fixture-assets/panels.js'));
    roots[variant] = root;
}
console.log(JSON.stringify(roots));
