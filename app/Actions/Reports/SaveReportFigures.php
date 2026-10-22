<?php

namespace App\Actions\Reports;

use App\Models\MonthlyReport;
use App\Models\OpeningBalance;
use App\Services\ReportBalanceCalculator;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Menyimpan angka laporan bulanan lalu menghitung ulang rantai saldo: `opening_*` bulan M+1.. diturunkan
 * dari saldo akhir bulan sebelumnya (docs/04 aturan 1). `computed_*` hanyalah cache dasbor.
 * Status tidak disentuh di sini (hanya ReportStateMachine, F-07).
 */
class SaveReportFigures
{
    public function __construct(private readonly ReportBalanceCalculator $calculator) {}

    /**
     * @param  array<string, mixed>  $figures  kolom angka laporan (string desimal) + `is_nil`, `difference_note`, `receipt_other_note`
     * @param  array<int, array{gross_amount: string, refund_amount: string}>  $lines  dikunci oleh expense_type_id
     */
    public function __invoke(MonthlyReport $report, array $figures, array $lines, int $lockVersion): MonthlyReport
    {
        return DB::transaction(function () use ($report, $figures, $lines, $lockVersion) {
            $updated = MonthlyReport::query()->withoutGlobalScopes()
                ->whereKey($report->id)->where('lock_version', $lockVersion)
                ->update(['lock_version' => $lockVersion + 1]);

            if ($updated === 0) {
                throw ValidationException::withMessages([
                    'lock_version' => 'Laporan diubah oleh pengguna lain. Muat ulang halaman lalu isi kembali.',
                ]);
            }

            $report->refresh();
            $report->fill($figures);
            $changed = $report->isDirty() || $this->linesChanged($report, $lines);
            $report->save();

            $report->lines()->delete();
            foreach ($lines as $typeId => $line) {
                $report->lines()->create(['expense_type_id' => $typeId, ...$line]);
            }

            // Angka berubah setelah disetujui kepala sekolah → persetujuan gugur (docs/06 §C.5).
            if ($changed && $report->principal_approved_at !== null) {
                $report->forceFill(['principal_approved_by' => null, 'principal_approved_at' => null])->save();
            }

            $this->recomputeFrom($report->refresh());

            return $report->refresh();
        });
    }

    /** @param  array<int, array{gross_amount: string, refund_amount: string}>  $lines */
    private function linesChanged(MonthlyReport $report, array $lines): bool
    {
        $current = $report->lines()->get()->keyBy('expense_type_id');
        $incoming = collect($lines)->filter(fn ($l) => ! (BigDecimal::of($l['gross_amount'])->isZero() && BigDecimal::of($l['refund_amount'])->isZero()));
        $existing = $current->filter(fn ($l) => ! (BigDecimal::of($l->gross_amount)->isZero() && BigDecimal::of($l->refund_amount)->isZero()));

        if ($incoming->keys()->sort()->values()->all() !== $existing->keys()->sort()->values()->all()) {
            return true;
        }

        return $incoming->contains(fn ($l, $id) => ! BigDecimal::of($l['gross_amount'])->isEqualTo($existing[$id]->gross_amount)
            || ! BigDecimal::of($l['refund_amount'])->isEqualTo($existing[$id]->refund_amount));
    }

    /** Hitung ulang `opening_*` dan cache untuk laporan ini serta bulan-bulan sesudahnya (sumber dana sama). */
    public function recomputeFrom(MonthlyReport $report): void
    {
        $chain = MonthlyReport::query()->withoutGlobalScopes()
            ->where('school_id', $report->school_id)
            ->where('fiscal_year_id', $report->fiscal_year_id)
            ->where('fund_source_id', $report->fund_source_id)
            ->where('month', '>=', $report->month)
            ->orderBy('month')
            ->with(['lines', 'adjustments'])
            ->get();

        $previousEnding = $this->previousEnding($report);

        foreach ($chain as $current) {
            $openingFund = $previousEnding['fund'] ?? $current->opening_fund;
            $openingTax = $previousEnding['tax'] ?? $current->opening_tax;

            // Laporan selesai yang saldo awalnya bergeser ditandai tinjau ulang; statusnya tidak diubah (docs/06 §F).
            $shifted = $current->id !== $report->id
                && $current->opening_fund !== null
                && ($current->opening_fund !== $openingFund || $current->opening_tax !== $openingTax);

            $current->opening_fund = $openingFund;
            $current->opening_tax = $openingTax;
            $result = $this->calculator->forReport($current);
            $current->computed_ending_fund = $result['ending_fund'];
            $current->computed_ending_tax = $result['ending_tax'];
            $current->computed_difference = $result['difference'];
            if ($shifted && $current->status->value === 'selesai_diperiksa') {
                $current->needs_rereview = true;
            }
            $current->save();

            $previousEnding = ['fund' => $result['ending_fund'], 'tax' => $result['ending_tax']];
        }
    }

    /** @return array{fund: ?string, tax: ?string} saldo akhir bulan sebelumnya, atau saldo awal tahun bila bulan pertama aktif. */
    private function previousEnding(MonthlyReport $report): array
    {
        $previous = MonthlyReport::query()->withoutGlobalScopes()
            ->where('school_id', $report->school_id)
            ->where('fiscal_year_id', $report->fiscal_year_id)
            ->where('fund_source_id', $report->fund_source_id)
            ->where('month', $report->month - 1)
            ->with(['lines', 'adjustments'])
            ->first();

        if ($previous !== null) {
            $result = $this->calculator->forReport($previous);

            return ['fund' => $result['ending_fund'], 'tax' => $result['ending_tax']];
        }

        $opening = OpeningBalance::query()->withoutGlobalScopes()
            ->where('school_id', $report->school_id)
            ->where('fiscal_year_id', $report->fiscal_year_id)
            ->where('fund_source_id', $report->fund_source_id)
            ->first();

        return ['fund' => $opening?->fund_amount ?? '0.00', 'tax' => $opening?->tax_amount ?? '0.00'];
    }
}
