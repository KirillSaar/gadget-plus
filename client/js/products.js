import { ENDPOINTS } from './config.js';
import { apiRequest } from './api.js';
import { renderUserBar, requireAuth } from './auth.js';
import { formatPrice } from './ui.js';

const tbody = document.getElementById('products-body');
const errorBox = document.getElementById('page-error');
const pageInfo = document.getElementById('page-info');
const prevButton = document.getElementById('prev');
const nextButton = document.getElementById('next');
const searchForm = document.getElementById('search-form');

let currentPage = 1;
let lastPage = 1;

if (requireAuth()) {
  init();
}

async function init() {
  try {
    await renderUserBar();
  } catch (error) {
    showError(error.message);
  }

  searchForm.addEventListener('submit', (event) => {
    event.preventDefault();
    currentPage = 1;
    loadProducts();
  });
  prevButton.addEventListener('click', () => changePage(currentPage - 1));
  nextButton.addEventListener('click', () => changePage(currentPage + 1));

  loadProducts();
}

function changePage(page) {
  if (page < 1 || page > lastPage) return;
  currentPage = page;
  loadProducts();
}

function showError(message) {
  errorBox.textContent = message;
}

function cell(text) {
  const td = document.createElement('td');
  td.textContent = text;
  return td;
}

async function loadProducts() {
  showError('');
  try {
    const body = await apiRequest(ENDPOINTS.products, {
      query: { search: searchForm.search.value.trim(), page: currentPage },
    });

    const products = body.data; // массив записей лежит в поле data
    const rows = products.map((product) => {
      const tr = document.createElement('tr');
      tr.append(
        cell(product.id),
        cell(product.name),
        cell(product.category?.name ?? '—'),
        cell(product.brand?.name ?? '—'),
        cell(formatPrice(product.price)),
        cell(product.stock),
      );
      return tr;
    });

    if (rows.length === 0) {
      const tr = document.createElement('tr');
      const td = cell('Ничего не найдено');
      td.colSpan = 6;
      tr.append(td);
      rows.push(tr);
    }
    tbody.replaceChildren(...rows);

    currentPage = body.meta?.current_page ?? 1;
    lastPage = body.meta?.last_page ?? 1;
    pageInfo.textContent = `Страница ${currentPage} из ${lastPage}`;
    prevButton.disabled = currentPage <= 1;
    nextButton.disabled = currentPage >= lastPage;
  } catch (error) {
    showError(error.message);
  }
}
