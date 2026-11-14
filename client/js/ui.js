/** Очищает старые сообщения об ошибках формы. */
export function clearErrors(form) {
  form.querySelectorAll('[data-error-for]').forEach((el) => (el.textContent = ''));
  const general = form.querySelector('.form-error');
  if (general) general.textContent = '';
}

/** Выводит ошибки валидации (объект errors от сервера) под полями формы. */
export function showFieldErrors(form, errors) {
  Object.entries(errors).forEach(([field, messages]) => {
    const target = form.querySelector(`[data-error-for="${field}"]`);
    if (target) target.textContent = Array.isArray(messages) ? messages[0] : messages;
  });
}

export function showFormError(form, message) {
  const general = form.querySelector('.form-error');
  if (general) general.textContent = message;
}

export function formatPrice(value) {
  return Number(value).toLocaleString('ru-RU') + ' ₸';
}
