<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachProductCharacteristicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'characteristic_id' => ['required', 'integer', 'exists:characteristics,id'],
            'value' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'present' => 'Поле «:attribute» должно присутствовать в запросе.',
            'array' => 'Поле «:attribute» должно быть массивом.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'string' => 'Поле «:attribute» должно быть строкой.',
            'max' => 'Поле «:attribute» не должно быть длиннее :max символов.',
            'exists' => 'Выбранная характеристика не найдена.',
            'distinct' => 'Характеристика указана несколько раз.',
        ];
    }

    public function attributes(): array
    {
        return [
            'characteristic_id' => 'характеристика',
            'value' => 'значение',
        ];
    }
}
