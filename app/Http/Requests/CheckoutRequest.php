<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\(\)]{7,20}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'address.required' => 'Укажите адрес доставки',
            'address.max' => 'Адрес не должен быть длиннее 255 символов',
            'phone.required' => 'Укажите телефон',
            'phone.regex' => 'Телефон указан в неверном формате',
        ];
    }
}
