import { readdir, readFile, writeFile } from 'node:fs/promises';
import { resolve, join } from 'node:path';
import { gzipSync } from 'node:zlib';

const root = resolve('public/build/assets');
let sourceBytes = 0, compressedBytes = 0, files = 0;
for (const entry of await readdir(root, { withFileTypes: true })) {
    if (!entry.isFile() || !/\.(css|js)$/.test(entry.name)) continue;
    const source = await readFile(join(root, entry.name));
    const compressed = gzipSync(source, { level: 9 });
    if (compressed.length >= source.length) continue;
    await writeFile(join(root, entry.name + '.gz'), compressed);
    files++; sourceBytes += source.length; compressedBytes += compressed.length;
}
console.log(JSON.stringify({ files, sourceBytes, compressedBytes }));
