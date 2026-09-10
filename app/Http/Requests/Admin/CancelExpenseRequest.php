<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CancelExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('expense.cancel');
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:500'],
        ];
    }
}
