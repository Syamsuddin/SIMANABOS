<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\ExpenseType;
use App\Models\MonthlyReport;
use Brick\Math\BigDecimal;

/**
 * Aturan validasi pengajuan angka (docs/06 §C). Hanya memeriksa; perubahan status tetap milik
 * ReportStateMachine (F-07), yang akan memanggil kelas ini.
 */
class ReportSubmissionValidator
{
    public function __construct(
        private readonly ReportBalanceCalculator $calculator,
        private readonly DocumentCompletenessChecker $completeness,
    ) {}

    /** @return list<string> pesan galat; kosong berarti lulus. */
    public function errors(MonthlyReport $report): array
    {
        $errors = [];

        $previous = MonthlyReport::query()->withoutGlobalScopes()
            ->where('school_id', $report->school_id)
            ->where('fiscal_year_id', $report->fiscal_year_id)
            ->where('fund_source_id', $report->fund_source_id)
            ->where('month', $report->month - 1)->first();

        if ($previous !== null && ! in_array($previous->status, [ReportStatus::Diajukan, ReportStatus::DalamPemeriksaan, ReportStatus::PerluPerbaikan, ReportStatus::SelesaiDiperiksa], true)) {
            $errors[] = 'Laporan bulan sebelumnya belum diajukan.';
        }
        // Bulan sebelumnya yang masih perlu perbaikan sudah pernah diajukan, jadi tidak menghalangi.

        if ($report->bank_balance_bku === null || $report->cash_balance_bku === null) {
            $errors[] = 'Saldo bank dan saldo tunai menurut BKU wajib diisi.';
        }

        if (BigDecimal::of($report->receipt_other ?? '0')->isPositive() && blank($report->receipt_other_note)) {
            $errors[] = 'Keterangan penerimaan lain wajib diisi bila penerimaan lain lebih dari nol.';
        }

        $errors = [...$errors, ...$this->completeness->errors($report), ...$this->negativeCumulativeErrors($report)];

        $result = $this->calculator->forReport($report->loadMissing(['lines', 'adjustments']));
        if ($result['difference'] !== null && ! BigDecimal::of($result['difference'])->isZero() && blank($report->difference_note)) {
            $errors[] = 'Selisih saldo tidak nol; catatan selisih wajib diisi.';
        }

        return $errors;
    }

    /** @return list<string> */
    private function negativeCumulativeErrors(MonthlyReport $report): array
    {
        $cumulative = [];
        $reports = MonthlyReport::query()->withoutGlobalScopes()
            ->where('school_id', $report->school_id)
            ->where('fiscal_year_id', $report->fiscal_year_id)
            ->where('fund_source_id', $report->fund_source_id)
            ->where('month', '<=', $report->month)
            ->with(['lines', 'adjustments'])->get();

        foreach ($reports as $item) {
            foreach ($this->calculator->forReport($item)['net_by_type'] as $typeId => $net) {
                $cumulative[$typeId] = ($cumulative[$typeId] ?? BigDecimal::zero())->plus($net);
            }
        }

        $names = ExpenseType::query()->whereIn('id', array_keys($cumulative))->pluck('name', 'id');
        $errors = [];
        foreach ($cumulative as $typeId => $total) {
            if ($total->isNegative()) {
                $errors[] = 'Realisasi neto kumulatif jenis belanja "'.($names[$typeId] ?? $typeId).'" bernilai negatif.';
            }
        }

        return $errors;
    }
}
