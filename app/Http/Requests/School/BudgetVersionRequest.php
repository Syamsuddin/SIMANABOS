<?php

namespace App\Http\Requests\School;

use App\Models\BudgetVersion;
use App\Models\ExpenseType;
use App\Models\FiscalYear;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BudgetVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $fiscalYear = FiscalYear::query()->find($this->input('fiscal_year_id'));

        return $fiscalYear !== null && $this->user()->can('create', [BudgetVersion::class, $fiscalYear]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'lines' => collect($this->input('lines', []))->map(fn ($amount) => Money::parse($amount) ?? '0.00')->all(),
            'reason' => $this->filled('reason') ? trim((string) $this->input('reason')) : null,
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            // RKAS hanya untuk sumber dana yang punya pagu di sekolah ini.
            'fund_source_id' => ['required', 'integer', Rule::exists('allocations', 'fund_source_id')
                ->where('school_id', $schoolId)->where('fiscal_year_id', $this->input('fiscal_year_id'))],
            'effective_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*' => ['regex:/^\d{1,13}\.\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.*.regex' => 'Jumlah harus berupa angka, contoh 26.512.500,00.',
            'effective_date.date_format' => 'Tanggal berlaku tidak valid.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $fiscalYear = FiscalYear::query()->findOrFail($this->integer('fiscal_year_id'));
            $date = $this->input('effective_date');

            if (! str_starts_with($date, (string) $fiscalYear->year)) {
                $validator->errors()->add('effective_date', "Tanggal berlaku harus berada di tahun anggaran {$fiscalYear->year}.");
            }

            $validTypeIds = ExpenseType::query()->where('fiscal_year_id', $fiscalYear->id)->where('is_active', true)->pluck('id')->all();
            foreach (array_keys($this->input('lines')) as $typeId) {
                if (! in_array((int) $typeId, $validTypeIds, true)) {
                    $validator->errors()->add('lines', 'Terdapat jenis belanja yang tidak berlaku pada tahun anggaran ini.');
                    break;
                }
            }

            $latest = BudgetVersion::query()
                ->where('school_id', $this->user()->school_id)
                ->where('fiscal_year_id', $fiscalYear->id)
                ->where('fund_source_id', $this->integer('fund_source_id'))
                ->orderByDesc('version_no')->first();

            if ($latest) {
                if ($this->input('reason') === null) {
                    $validator->errors()->add('reason', 'Alasan perubahan wajib diisi untuk versi RKAS berikutnya.');
                }
                if ($date <= $latest->effective_date->toDateString()) {
                    $validator->errors()->add('effective_date', 'Tanggal berlaku harus setelah tanggal berlaku versi sebelumnya ('.$latest->effective_date->format('d-m-Y').').');
                }
            }
        }];
    }
}
