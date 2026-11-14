// Единственное место, где задаются адрес API и пути запросов.
// Сверьте пути со списком маршрутов: php artisan route:list --path=api
export const API_BASE = 'http://127.0.0.1:8000/api';

export const ENDPOINTS = {
  register: '/v1/auth/register',
  login: '/v1/auth/login',
  logout: '/v1/auth/logout',
  me: '/v1/auth/me',
  products: '/v1/products',
};
