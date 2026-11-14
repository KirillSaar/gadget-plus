import { login, redirectIfAuthenticated } from './auth.js';
import { ApiError } from './api.js';
import { clearErrors, showFieldErrors, showFormError } from './ui.js';

redirectIfAuthenticated();

const form = document.getElementById('login-form');

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  clearErrors(form);

  try {
    await login(form.email.value.trim(), form.password.value);
    window.location.href = 'products.html';
  } catch (error) {
    if (error instanceof ApiError && error.status === 422) {
      showFieldErrors(form, error.errors);
    } else if (error instanceof ApiError && error.status === 401) {
      showFormError(form, 'Неверный email или пароль.');
    } else {
      showFormError(form, error.message);
    }
  }
});
