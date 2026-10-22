<?php

namespace App\Http\Requests\School;

use App\Enums\PeriodicKind;
use App\Models\FiscalYear;
use App\Models\PeriodicReport;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PeriodicReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $fiscalYear = FiscalYear::query()->find($this->input('fiscal_year_id'));

        return $fiscalYear !== null && $this->user()->can('save', [PeriodicReport::class, $fiscalYear]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reported_total_realization' => Money::parse($this->input('reported_total_realization')),
            'reported_ending_balance' => Money::parse($this->input('reported_ending_balance')),
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $money = ['nullable', 'regex:/^\d{1,13}\.\d{2}$/'];
        $isStage = $this->input('kind') === PeriodicKind::Stage->value;

        return [
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'fund_source_id' => ['required', 'integer', Rule::exists('allocations', 'fund_source_id')
                ->where('school_id', $this->user()->school_id)->where('fiscal_year_id', $this->input('fiscal_year_id'))],
            'kind' => ['required', Rule::enum(PeriodicKind::class)],
            'stage_no' => $isStage
                ? ['required', 'integer', Rule::exists('fiscal_year_stages', 'stage_no')->where('fiscal_year_id', $this->input('fiscal_year_id'))]
                : ['nullable'],
            'reported_total_realization' => $money,
            'reported_ending_balance' => $money,
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reported_total_realization.regex' => 'Total realisasi harus berupa angka rupiah, contoh 26.512.500,00.',
            'reported_ending_balance.regex' => 'Saldo akhir harus berupa angka rupiah, contoh 26.512.500,00.',
            'stage_no.exists' => 'Tahap tersebut belum ditetapkan Dinas untuk tahun ini.',
            'fund_source_id.exists' => 'Sumber dana belum memiliki pagu untuk sekolah Anda.',
        ];
    }
}
