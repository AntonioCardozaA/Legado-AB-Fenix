import { readFile } from 'node:fs/promises';
import { join } from 'node:path';

const root = process.argv[2];
if (!root) throw new Error('Pass the comparison output directory.');
let failed = false;
for (const page of (process.argv[3] || 'pasteurizer,hydraulics').split(',')) for (const width of [1440, 390]) {
    const before = JSON.parse(await readFile(join(root, `before-${page}-${width}.json`), 'utf8'));
    const after = JSON.parse(await readFile(join(root, `after-${page}-${width}.json`), 'utf8'));
    const stylesEqual = JSON.stringify(before.styles) === JSON.stringify(after.styles);
    const controlsEqual = JSON.stringify(before.dashboardChecks) === JSON.stringify(after.dashboardChecks);
    const modals = after.dashboardChecks?.modals || [];
    const modalsWork = modals.length > 0 && modals.every((modal) => modal.open && modal.closed);
    failed ||= !stylesEqual || !controlsEqual || !modalsWork;
    console.log(JSON.stringify({ page, width, stylesEqual, chartsAndControlsEqual: controlsEqual, modalCases: modals.length, modalsWork }));
}
process.exitCode = failed ? 1 : 0;
