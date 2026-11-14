<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Укажите товар',
            'product_id.integer' => 'Идентификатор товара должен быть числом',
            'product_id.exists' => 'Указанный товар не существует',
            'quantity.required' => 'Укажите количество',
            'quantity.integer' => 'Количество должно быть целым числом',
            'quantity.min' => 'Количество должно быть не меньше 1',
            'quantity.max' => 'Количество не может быть больше 1000',
        ];
    }
}
