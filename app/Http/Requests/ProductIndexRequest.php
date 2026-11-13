<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $priceMax = ['nullable', 'numeric', 'min:0'];
        if ($this->filled('price_min')) {
            $priceMax[] = 'gte:price_min';
        }

        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => $priceMax,
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['id', 'name', 'price', 'created_at'])],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.integer' => 'Категория должна быть числом',
            'category_id.exists' => 'Указанная категория не существует',
            'brand_id.integer' => 'Бренд должен быть числом',
            'brand_id.exists' => 'Указанный бренд не существует',
            'price_min.numeric' => 'Минимальная цена должна быть числом',
            'price_min.min' => 'Минимальная цена не может быть отрицательной',
            'price_max.numeric' => 'Максимальная цена должна быть числом',
            'price_max.min' => 'Максимальная цена не может быть отрицательной',
            'price_max.gte' => 'Максимальная цена не может быть меньше минимальной',
            'search.max' => 'Строка поиска не должна быть длиннее 100 символов',
            'sort.in' => 'Сортировка возможна по полям: id, name, price, created_at',
            'order.in' => 'Направление сортировки: asc или desc',
            'page.integer' => 'Номер страницы должен быть целым числом',
            'page.min' => 'Номер страницы должен быть не меньше 1',
            'per_page.integer' => 'Размер страницы должен быть целым числом',
            'per_page.min' => 'Размер страницы должен быть не меньше 1',
            'per_page.max' => 'Размер страницы не может быть больше 50',
        ];
    }
}