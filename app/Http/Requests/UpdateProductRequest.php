<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'brand_id' => ['sometimes', 'required', 'integer', 'exists:brands,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('products', 'slug')->ignore($this->route('product')),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'characteristics' => ['sometimes', 'array'],
            'characteristics.*.id' => ['required', 'integer', 'exists:characteristics,id'],
            'characteristics.*.value' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'string' => 'Поле «:attribute» должно быть строкой.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'numeric' => 'Поле «:attribute» должно быть числом.',
            'boolean' => 'Поле «:attribute» должно быть true или false.',
            'array' => 'Поле «:attribute» должно быть массивом.',
            'max' => 'Поле «:attribute» не должно быть длиннее :max символов.',
            'exists' => 'Выбранное значение поля «:attribute» не найдено.',
            'unique' => 'Значение поля «:attribute» уже занято.',
            'price.min' => 'Цена должна быть больше нуля.',
            'stock.min' => 'Остаток не может быть отрицательным.',
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'категория',
            'brand_id' => 'бренд',
            'name' => 'название',
            'slug' => 'адрес (slug)',
            'description' => 'описание',
            'price' => 'цена',
            'stock' => 'остаток',
            'is_active' => 'активность',
            'characteristics' => 'характеристики',
            'characteristics.*.id' => 'характеристика',
            'characteristics.*.value' => 'значение характеристики',
        ];
    }
}
