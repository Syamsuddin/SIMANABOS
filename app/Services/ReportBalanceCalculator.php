<?php

namespace App\Services;

use App\Enums\AdjustmentKind;
use App\Models\MonthlyReport;
use Brick\Math\BigDecimal;

/**
 * Rumah tunggal rumus saldo (docs/04 §Rumus saldo). Seluruh aritmetika memakai BigDecimal
 * dengan skala 2; hasil berupa string desimal.
 */
class ReportBalanceCalculator
{
    /**
     * @param  array{
     *     opening_fund?: ?string, opening_tax?: ?string, receipt_bos?: ?string, receipt_other?: ?string,
     *     treasury_deposit?: ?string, tax_withheld?: ?string, tax_remitted?: ?string,
     *     bank_balance_bku?: ?string, cash_balance_bku?: ?string,
     *     lines?: array<int|string, array{gross_amount?: ?string, refund_amount?: ?string}>,
     *     adjustments?: array<int, array{kind: string, amount: string, expense_type_id?: ?int}>
     * }  $input
     * @return array{total_net_realization: string, ending_fund: string, ending_tax: string, cash_calculated: string, cash_bku: ?string, difference: ?string, net_by_type: array<int|string, string>}
     */
    public function calculate(array $input): array
    {
        $netByType = [];
        $totalNet = $this->dec(null);
        foreach ($input['lines'] ?? [] as $typeId => $line) {
            $net = $this->dec($line['gross_amount'] ?? null)->minus($this->dec($line['refund_amount'] ?? null));
            $netByType[$typeId] = $net;
            $totalNet = $totalNet->plus($net);
        }

        $otherAdjustments = $this->dec(null);
        foreach ($input['adjustments'] ?? [] as $adjustment) {
            $amount = $this->dec($adjustment['amount']);
            if ($adjustment['kind'] === AdjustmentKind::Realisasi->value) {
                $typeId = $adjustment['expense_type_id'] ?? null;
                $netByType[$typeId] = ($netByType[$typeId] ?? $this->dec(null))->plus($amount);
                $totalNet = $totalNet->plus($amount);
            } else {
                $otherAdjustments = $otherAdjustments->plus($amount);
            }
        }

        $endingFund = $this->dec($input['opening_fund'] ?? null)
            ->plus($this->dec($input['receipt_bos'] ?? null))
            ->plus($this->dec($input['receipt_other'] ?? null))
            ->minus($totalNet)
            ->minus($this->dec($input['treasury_deposit'] ?? null))
            ->plus($otherAdjustments);

        $endingTax = $this->dec($input['opening_tax'] ?? null)
            ->plus($this->dec($input['tax_withheld'] ?? null))
            ->minus($this->dec($input['tax_remitted'] ?? null));

        $cashCalculated = $endingFund->plus($endingTax);

        $bank = $input['bank_balance_bku'] ?? null;
        $cash = $input['cash_balance_bku'] ?? null;
        $cashBku = ($bank === null || $cash === null) ? null : $this->dec($bank)->plus($this->dec($cash));

        return [
            'total_net_realization' => (string) $totalNet,
            'ending_fund' => (string) $endingFund,
            'ending_tax' => (string) $endingTax,
            'cash_calculated' => (string) $cashCalculated,
            'cash_bku' => $cashBku === null ? null : (string) $cashBku,
            'difference' => $cashBku === null ? null : (string) $cashBku->minus($cashCalculated),
            'net_by_type' => array_map(fn (BigDecimal $v) => (string) $v, $netByType),
        ];
    }

    /** Hitung dari laporan tersimpan (angka tulis-ulang oleh `opening_*` sistem). */
    public function forReport(MonthlyReport $report): array
    {
        return $this->calculate([
            ...$report->only(['opening_fund', 'opening_tax', 'receipt_bos', 'receipt_other', 'treasury_deposit', 'tax_withheld', 'tax_remitted', 'bank_balance_bku', 'cash_balance_bku']),
            'lines' => $report->lines->mapWithKeys(fn ($l) => [$l->expense_type_id => ['gross_amount' => $l->gross_amount, 'refund_amount' => $l->refund_amount]])->all(),
            'adjustments' => $report->adjustments->map(fn ($a) => ['kind' => $a->kind->value, 'amount' => $a->amount, 'expense_type_id' => $a->expense_type_id])->all(),
        ]);
    }

    private function dec(?string $value): BigDecimal
    {
        return BigDecimal::of($value ?? '0')->toScale(2);
    }
}
