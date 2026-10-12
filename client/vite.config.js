import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

// TLS verification stays enabled unless explicitly disabled for a known local host.
export function createProxyOptions(env = {}) {
  const target = env.VITE_LARAVEL_URL || 'https://bookmystyle.test'
  const url = new URL(target)
  if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password) {
    throw new Error('The development proxy requires an HTTP(S) target without embedded credentials.')
  }
  const local = ['localhost', '127.0.0.1', '[::1]', 'bookmystyle.test'].includes(url.hostname)
  if (url.protocol === 'http:' && !local) {
    throw new Error('Remote development proxies require HTTPS.')
  }
  const insecure = env.VITE_DEV_PROXY_INSECURE === 'true'
  if (insecure && !local) {
    throw new Error('TLS verification can only be disabled for the local development proxy.')
  }
  return { target, changeOrigin: true, secure: !insecure }
}

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const proxy = createProxyOptions(env)

  return {
    plugins: [react()],
    server: {
      proxy: {
        '/api': {
          ...proxy
        },
        '/storage': {
          ...proxy
        }
      }
    }
  }
})
