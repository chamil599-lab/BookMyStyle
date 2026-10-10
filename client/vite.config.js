import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const laravelUrl = env.VITE_LARAVEL_URL || 'https://bookmystyle.test'

  return {
    plugins: [react()],
    server: {
      proxy: {
        '/api': {
          target: laravelUrl,
          changeOrigin: true,
          secure: false,
          rewrite: (path) => path
        },
        '/storage': {
          target: laravelUrl,
          changeOrigin: true,
          secure: false,
          rewrite: (path) => path
        }
      }
    }
  }
})
