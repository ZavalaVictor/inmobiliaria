import path from 'node:path'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'

const publicRoot = path.resolve(process.cwd())
const dependenciesRoot = path.resolve(publicRoot, '../frontend/node_modules')

export default defineConfig({
  root: publicRoot,
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: [
      { find: /^react$/, replacement: path.resolve(dependenciesRoot, 'react/index.js') },
      { find: /^react\/jsx-runtime$/, replacement: path.resolve(dependenciesRoot, 'react/jsx-runtime.js') },
      { find: /^react\/jsx-dev-runtime$/, replacement: path.resolve(dependenciesRoot, 'react/jsx-dev-runtime.js') },
      { find: /^react-dom$/, replacement: path.resolve(dependenciesRoot, 'react-dom/index.js') },
      { find: /^react-dom\/client$/, replacement: path.resolve(dependenciesRoot, 'react-dom/client.js') },
    ],
  },
  server: {
    host: '127.0.0.1',
    port: 5174,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
      '/sanctum': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
})
