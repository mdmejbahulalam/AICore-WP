import { mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const sourceDir = path.join(root, 'assets/admin/src');
const distDir = path.join(root, 'assets/admin/dist');
const manifestDir = path.join(distDir, '.vite');

const banner = '/* Built admin asset. Do not edit directly; run `npm run build`. */\n';

async function build() {
  await rm(distDir, { recursive: true, force: true });
  await mkdir(manifestDir, { recursive: true });

  const [adminJs, adminCss] = await Promise.all([
    readFile(path.join(sourceDir, 'admin.js'), 'utf8'),
    readFile(path.join(sourceDir, 'admin.css'), 'utf8'),
  ]);

  await Promise.all([
    writeFile(path.join(distDir, 'admin.js'), `${banner}${adminJs}`),
    writeFile(path.join(distDir, 'admin.css'), `${banner}${adminCss}`),
  ]);

  const manifest = {
    'assets/admin/src/admin.js': {
      file: 'admin.js',
      src: 'assets/admin/src/admin.js',
      isEntry: true,
      css: ['admin.css'],
    },
  };

  await writeFile(
    path.join(manifestDir, 'manifest.json'),
    `${JSON.stringify(manifest, null, 2)}\n`,
  );
}

build().catch((error) => {
  console.error(error);
  process.exit(1);
});
