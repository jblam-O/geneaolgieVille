import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  root: 'frontend',
  base: '/genealogy/',
  plugins: [react()],
  build: {
    outDir: '../public/genealogy',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      output: {
        entryFileNames: 'assets/app-v4.js',
        chunkFileNames: 'assets/[name]-v4.js',
        assetFileNames: 'assets/[name]-v4[extname]',
      },
    },
  },
});
