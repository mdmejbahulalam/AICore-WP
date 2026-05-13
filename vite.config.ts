import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

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
