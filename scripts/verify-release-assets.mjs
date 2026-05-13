import { access, readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const distDir = path.join(root, 'assets/admin/dist');
const manifestPath = path.join(distDir, '.vite/manifest.json');

async function assertFile(relativePath) {
  await access(path.join(distDir, relativePath));
}

async function verify() {
  let manifest;

  try {
    manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
  } catch (error) {
    throw new Error(
      `Missing or invalid admin asset manifest at ${path.relative(root, manifestPath)}. Run npm run build before packaging.`,
      { cause: error },
    );
  }

  const entries = Object.values(manifest);
  if (entries.length === 0) {
    throw new Error('Admin asset manifest is empty.');
  }

  for (const entry of entries) {
    if (!entry.file) {
      throw new Error('Admin asset manifest entry is missing a file property.');
    }

    await assertFile(entry.file);

    for (const cssFile of entry.css || []) {
      await assertFile(cssFile);
    }
  }

  console.log(`Verified admin assets and manifest at ${path.relative(root, manifestPath)}.`);
}

verify().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
