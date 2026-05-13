import { defineConfig } from 'vite';

export default defineConfig({
  build: {
    emptyOutDir: true,
    manifest: '.vite/manifest.json',
    outDir: 'assets/admin/dist',
    rollupOptions: {
      input: {
        admin: 'assets/admin/src/admin.js',
      },
    },
  },
});
