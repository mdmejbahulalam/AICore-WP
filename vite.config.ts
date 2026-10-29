import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// The WordPress admin loads the bundle through AdminApp, which resolves file names from
// assets/admin/dist/.vite/manifest.json using the entry key below.
export default defineConfig({
  plugins: [react()],
  build: {
    outDir: 'assets/admin/dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: 'assets/admin/src/main.tsx',
      output: {
        entryFileNames: 'aicore-admin.js',
        assetFileNames: 'aicore-admin.[ext]'
      }
    }
  }
});
