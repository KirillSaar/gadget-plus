import { API_BASE } from './config.js';

const TOKEN_KEY = 'token';
const USER_KEY = 'user';

export const getToken = () => localStorage.getItem(TOKEN_KEY);
export const setToken = (token) => localStorage.setItem(TOKEN_KEY, token);
export const clearSession = () => {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
};

/** Ошибка API: status — HTTP-код, errors — объект ошибок валидации (код 422). */
export class ApiError extends Error {
  constructor(status, message, errors = null) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

/**
 * Общая функция запросов к API.
 *  - добавляет Accept, Content-Type и Authorization: Bearer <token>;
 *  - 401: удаляет токен и перенаправляет на страницу входа;
 *  - 422: бросает ApiError с объектом errors;
 *  - ошибки сети и сервера (5xx) превращает в понятные сообщения.
 * Для входа и регистрации передавайте auth: false (401 там означает неверный пароль).
 */
export async function apiRequest(path, { method = 'GET', body = null, query = null, auth = true } = {}) {
  const headers = { Accept: 'application/json' };
  if (body !== null) headers['Content-Type'] = 'application/json';

  const token = getToken();
  if (auth && token) headers.Authorization = `Bearer ${token}`;

  let url = API_BASE + path;
  if (query) {
    const params = new URLSearchParams();
    Object.entries(query).forEach(([key, value]) => {
      if (value !== '' && value !== null && value !== undefined) params.set(key, value);
    });
    const qs = params.toString();
    if (qs) url += '?' + qs;
  }

  let response;
  try {
    response = await fetch(url, {
      method,
      headers,
      body: body !== null ? JSON.stringify(body) : null,
    });
  } catch (networkError) {
    throw new ApiError(0, 'Не удалось связаться с сервером. Проверьте, что API запущено и разрешён CORS.');
  }

  if (response.status === 204) return null;

  let data = null;
  try {
    data = await response.json();
  } catch (parseError) {
    data = null;
  }

  if (response.ok) return data;

  if (response.status === 401 && auth) {
    clearSession();
    window.location.href = 'login.html';
    throw new ApiError(401, 'Сессия истекла. Войдите заново.');
  }

  if (response.status === 422) {
    throw new ApiError(422, data?.message ?? 'Проверьте введённые данные.', data?.errors ?? {});
  }

  if (response.status >= 500) {
    throw new ApiError(response.status, `Ошибка сервера (${response.status}). Попробуйте позже.`);
  }

  throw new ApiError(response.status, data?.message ?? `Ошибка запроса (${response.status}).`);
}
