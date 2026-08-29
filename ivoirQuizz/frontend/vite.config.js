import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

// The panel talks to Laravel through a dev proxy rather than an absolute URL:
// same-origin requests keep CORS out of the picture entirely, and the built
// bundle can be served from the Laravel host without changing a line.
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
    plugins: [react()],
    server: {
      port: 5173,
      proxy: {
        '/api': {
          target: env.VITE_API_TARGET || 'http://127.0.0.1:8000',
          changeOrigin: true,
        },
      },
    },
  }
})
