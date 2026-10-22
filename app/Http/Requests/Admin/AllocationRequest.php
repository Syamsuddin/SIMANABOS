<?php

namespace App\Http\Requests\Admin;

use App\Models\Allocation;
use App\Models\FiscalYear;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $fiscalYear = FiscalYear::query()->find($this->input('fiscal_year_id'));

        return $fiscalYear !== null && $this->user()->can('manage', [Allocation::class, $fiscalYear]);
    }

    protected function prepareForValidation(): void
    {
        $rows = collect($this->input('rows', []))
            ->map(fn ($row) => [
                'amount' => Money::parse($row['amount'] ?? null),
                'active_from_month' => ($row['active_from_month'] ?? '') === '' ? 1 : $row['active_from_month'],
            ])
            ->all();

        $this->merge(['rows' => $rows]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'fund_source_id' => ['required', 'integer', Rule::exists('fund_sources', 'id')->where('is_active', true)],
            'rows' => ['array'],
            'rows.*' => ['array'],
            'rows.*.amount' => ['nullable', 'regex:/^\d{1,13}\.\d{2}$/'],
            'rows.*.active_from_month' => ['integer', 'between:1,12'],
        ];
    }

    public function messages(): array
    {
        return [
            'rows.*.amount.regex' => 'Pagu harus berupa angka, contoh 26.512.500,00.',
            'rows.*.active_from_month.between' => 'Bulan mulai aktif harus antara 1 dan 12.',
        ];
    }
}
