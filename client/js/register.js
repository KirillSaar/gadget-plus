import { register, redirectIfAuthenticated } from './auth.js';
import { ApiError } from './api.js';
import { clearErrors, showFieldErrors, showFormError } from './ui.js';

redirectIfAuthenticated();

const form = document.getElementById('register-form');

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  clearErrors(form);

  const payload = {
    name: form.name.value.trim(),
    email: form.email.value.trim(),
    password: form.password.value,
    password_confirmation: form.password_confirmation.value,
  };
  if (form.phone.value.trim() !== '') payload.phone = form.phone.value.trim();

  try {
    await register(payload);
    window.location.href = 'products.html';
  } catch (error) {
    if (error instanceof ApiError && error.status === 422) {
      showFieldErrors(form, error.errors);
      showFormError(form, error.message);
    } else {
      showFormError(form, error.message);
    }
  }
});
