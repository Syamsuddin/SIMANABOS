<?php

namespace App\Http\Requests\School;

use App\Models\FiscalYear;
use App\Models\OpeningBalance;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpeningBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $fiscalYear = FiscalYear::query()->find($this->input('fiscal_year_id'));
        if ($fiscalYear === null) {
            return false;
        }

        $existing = OpeningBalance::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->where('fund_source_id', $this->input('fund_source_id'))
            ->first();

        return $this->user()->can('save', [OpeningBalance::class, $fiscalYear, $existing]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fund_amount' => Money::parse($this->input('fund_amount')),
            'tax_amount' => Money::parse($this->input('tax_amount')) ?? '0.00',
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'fund_source_id' => ['required', 'integer', Rule::exists('allocations', 'fund_source_id')
                ->where('school_id', $this->user()->school_id)->where('fiscal_year_id', $this->input('fiscal_year_id'))],
            'fund_amount' => ['required', 'regex:/^\d{1,13}\.\d{2}$/'],
            'tax_amount' => ['required', 'regex:/^\d{1,13}\.\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'fund_amount.regex' => 'Saldo dana harus berupa angka, contoh 26.512.500,00.',
            'tax_amount.regex' => 'Saldo pajak harus berupa angka, contoh 1.250.000,00.',
        ];
    }
}
