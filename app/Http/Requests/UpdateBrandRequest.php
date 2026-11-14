<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($this->route('brand'))],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($this->route('brand'))],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'string' => 'Поле «:attribute» должно быть строкой.',
            'max' => 'Поле «:attribute» не должно быть длиннее :max символов.',
            'unique' => 'Значение поля «:attribute» уже занято.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'название',
            'slug' => 'адрес (slug)',
        ];
    }
}
