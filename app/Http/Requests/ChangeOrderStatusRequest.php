<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Укажите новый статус',
            'status.enum' => 'Недопустимый статус. Возможные: new, paid, shipped, delivered, cancelled',
        ];
    }
}
