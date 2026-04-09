import axios from 'axios';

const API_BASE = `http://${window.location.hostname}:8000`; // Dynamically use the current IP/localhost

const api = axios.create({
  baseURL: API_BASE,
  headers: { 'Content-Type': 'application/json' },
});

// Attach JWT token to every request automatically
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// Handle 401 globally — clear token and redirect to login.
// EXCEPTION: skip redirect for the /api/auth/me startup check,
// which has its own error handler in AuthContext that silently
// clears the stale token without disrupting the current page.
api.interceptors.response.use(
  (res) => res,
  (err) => {
    const isStartupCheck = err.config?.url?.includes('/api/auth/me');
    if (err.response?.status === 401 && !isStartupCheck) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(err);
  }
);

export default api;
