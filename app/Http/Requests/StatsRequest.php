<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dateTo = ['nullable', 'date'];
        if ($this->filled('date_from')) {
            $dateTo[] = 'after_or_equal:date_from';
        }

        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => $dateTo,
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.date' => 'Дата начала указана в неверном формате',
            'date_to.date' => 'Дата окончания указана в неверном формате',
            'date_to.after_or_equal' => 'Дата окончания не может быть раньше даты начала',
            'limit.integer' => 'Лимит должен быть целым числом',
            'limit.min' => 'Лимит должен быть не меньше 1',
            'limit.max' => 'Лимит не может быть больше 20',
        ];
    }
}
