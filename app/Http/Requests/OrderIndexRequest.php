<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderIndexRequest extends FormRequest
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
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => $dateTo,
            'sort' => ['nullable', Rule::in(['id', 'total', 'created_at'])],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'Недопустимый статус заказа',
            'date_from.date' => 'Дата начала указана в неверном формате',
            'date_to.date' => 'Дата окончания указана в неверном формате',
            'date_to.after_or_equal' => 'Дата окончания не может быть раньше даты начала',
            'sort.in' => 'Сортировка возможна по полям: id, total, created_at',
            'order.in' => 'Направление сортировки: asc или desc',
            'page.integer' => 'Номер страницы должен быть целым числом',
            'page.min' => 'Номер страницы должен быть не меньше 1',
            'per_page.integer' => 'Размер страницы должен быть целым числом',
            'per_page.min' => 'Размер страницы должен быть не меньше 1',
            'per_page.max' => 'Размер страницы не может быть больше 50',
        ];
    }
}
