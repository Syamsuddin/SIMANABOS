<?php

namespace App\Http\Requests\Admin;

use App\Models\ExpenseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expenseType = $this->route('expense_type');

        return $expenseType
            ? $this->user()->can('update', $expenseType)
            : $this->user()->can('create', [ExpenseType::class, $this->route('fiscal_year')]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order') === null || $this->input('sort_order') === '' ? 0 : $this->input('sort_order'),
            'arkas_account_code' => $this->filled('arkas_account_code') ? $this->input('arkas_account_code') : null,
        ]);
    }

    /**
     * Kode tidak diubah setelah dibuat; hanya nama, kode rekening ARKAS, urutan, dan status aktif.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'arkas_account_code' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['integer', 'between:0,32767'],
            'is_active' => ['boolean'],
        ];

        if (! $this->route('expense_type')) {
            $rules['code'] = [
                'required', 'string', 'max:30', 'regex:/^[A-Z0-9_]+$/',
                Rule::unique('expense_types', 'code')->where('fiscal_year_id', $this->route('fiscal_year')->id),
            ];
        }

        return $rules;
    }
}
