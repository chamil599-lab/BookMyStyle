import axios from 'axios';

const api = axios.create({
  // Use VITE_API_BASE_URL when provided, otherwise default to a relative /api
  // This makes local development work without needing to point to the production API.
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Add token to requests if it exists
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
      config.headers['X-Authorization'] = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Handle 401 unauthorized responses
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');

      const isLoginPage = window.location.pathname === '/login';
      const isAuthRequest = error.config?.url?.includes('/auth/login');

      if (!isLoginPage && !isAuthRequest) {
        window.location.assign('/login');
      }
    }
    return Promise.reject(error);
  }
);

export default api;

export const asArray = (payload) => {
  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload?.data)) return payload.data;
  if (Array.isArray(payload?.items)) return payload.items;
  if (Array.isArray(payload?.results)) return payload.results;
  return [];
};

export const asObject = (payload, fallback = {}) => {
  if (payload && typeof payload === 'object' && !Array.isArray(payload)) return payload;
  return fallback;
};
