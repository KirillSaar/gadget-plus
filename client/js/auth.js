import { ENDPOINTS } from './config.js';
import { apiRequest, clearSession, getToken, setToken } from './api.js';

const USER_KEY = 'user';

function extractToken(body) {
  return body?.token ?? body?.data?.token ?? body?.access_token ?? body?.data?.access_token ?? null;
}

export async function login(email, password) {
  const body = await apiRequest(ENDPOINTS.login, { method: 'POST', body: { email, password }, auth: false });
  const token = extractToken(body);
  if (!token) throw new Error('Сервер не вернул токен. Проверьте путь входа в js/config.js.');
  setToken(token);
}

export async function register(payload) {
  const body = await apiRequest(ENDPOINTS.register, { method: 'POST', body: payload, auth: false });
  const token = extractToken(body);

  if (token) {
    setToken(token);
  } else {
    // Если регистрация не выдаёт токен, входим отдельным запросом
    await login(payload.email, payload.password);
  }
}

/** Текущий пользователь (запрос к API с токеном). */
export async function fetchCurrentUser() {
  const body = await apiRequest(ENDPOINTS.me);
  const user = body?.data ?? body;
  localStorage.setItem(USER_KEY, JSON.stringify(user));
  return user;
}

export async function logout() {
  try {
    await apiRequest(ENDPOINTS.logout, { method: 'POST' });
  } catch (error) {
    // Токен на сервере мог уже истечь: на клиенте всё равно выходим
  }
  clearSession();
  window.location.href = 'login.html';
}

/** Страницы только для авторизованных. */
export function requireAuth() {
  if (!getToken()) {
    window.location.href = 'login.html';
    return false;
  }
  return true;
}

/** Страницы входа и регистрации: если токен уже есть, уходим в каталог. */
export function redirectIfAuthenticated() {
  if (getToken()) window.location.href = 'products.html';
}

/** Показывает в шапке имя и роль пользователя и кнопку выхода. */
export async function renderUserBar() {
  const bar = document.getElementById('user-bar');
  if (!bar) return;

  const user = await fetchCurrentUser();

  const text = document.createElement('span');
  text.textContent = `${user.name} (${user.role_label ?? user.role})`;

  const button = document.createElement('button');
  button.type = 'button';
  button.textContent = 'Выйти';
  button.addEventListener('click', logout);

  bar.replaceChildren(text, button);
}
