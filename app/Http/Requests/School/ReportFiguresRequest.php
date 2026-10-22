<?php

namespace App\Http\Requests\School;

use App\Models\ExpenseType;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

class ReportFiguresRequest extends FormRequest
{
    private const MONEY_FIELDS = ['receipt_bos', 'receipt_other', 'treasury_deposit', 'tax_withheld', 'tax_remitted', 'bank_balance_bku', 'cash_balance_bku'];

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('report'));
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (self::MONEY_FIELDS as $field) {
            $merge[$field] = Money::parse($this->input($field));
        }

        $merge['lines'] = collect($this->input('lines', []))->map(fn ($line) => [
            'gross_amount' => Money::parse($line['gross_amount'] ?? null) ?? '0.00',
            'refund_amount' => Money::parse($line['refund_amount'] ?? null) ?? '0.00',
        ])->all();

        foreach (['receipt_other_note', 'difference_note'] as $field) {
            $merge[$field] = $this->filled($field) ? trim((string) $this->input($field)) : null;
        }

        $this->merge($merge);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $money = ['nullable', 'regex:/^\d{1,13}\.\d{2}$/'];

        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'is_nil' => ['boolean'],
            'receipt_bos' => $money,
            'receipt_other' => $money,
            'receipt_other_note' => ['nullable', 'string', 'max:255'],
            'treasury_deposit' => $money,
            'tax_withheld' => $money,
            'tax_remitted' => $money,
            'bank_balance_bku' => $money,
            'cash_balance_bku' => $money,
            'difference_note' => ['nullable', 'string', 'max:2000'],
            'lines' => ['array'],
            'lines.*.gross_amount' => ['regex:/^\d{1,13}\.\d{2}$/'],
            'lines.*.refund_amount' => ['regex:/^\d{1,13}\.\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.regex' => 'Jumlah harus berupa angka tidak negatif, contoh 26.512.500,00.',
            'lines.*.*.regex' => 'Jumlah harus berupa angka tidak negatif, contoh 26.512.500,00.',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $report = $this->route('report');
            $validIds = ExpenseType::query()->where('fiscal_year_id', $report->fiscal_year_id)->where('is_active', true)->pluck('id')->all();
            foreach (array_keys($this->input('lines', [])) as $typeId) {
                if (! in_array((int) $typeId, $validIds, true)) {
                    $validator->errors()->add('lines', 'Terdapat jenis belanja yang tidak berlaku pada tahun anggaran ini.');
                    break;
                }
            }
        }];
    }

    /** Angka siap simpan: `opening_*` tidak pernah dari masukan; laporan nihil menolkan penerimaan dan rincian. */
    public function figures(): array
    {
        $nil = $this->boolean('is_nil');
        $figures = ['is_nil' => $nil];
        foreach (self::MONEY_FIELDS as $field) {
            $figures[$field] = $this->input($field);
        }
        foreach (['receipt_bos', 'receipt_other', 'treasury_deposit', 'tax_withheld', 'tax_remitted'] as $field) {
            $figures[$field] ??= '0.00';
        }
        if ($nil) {
            $figures['receipt_bos'] = $figures['receipt_other'] = '0.00';
        }
        $figures['receipt_other_note'] = $this->input('receipt_other_note');
        $figures['difference_note'] = $this->input('difference_note');

        return $figures;
    }

    /** @return array<int, array{gross_amount: string, refund_amount: string}> */
    public function expenseLines(): array
    {
        if ($this->boolean('is_nil')) {
            return [];
        }

        return collect($this->input('lines', []))->mapWithKeys(fn ($line, $typeId) => [(int) $typeId => $line])->all();
    }
}
