<?php

namespace App\Actions\FiscalYears;

use App\Enums\FiscalYearStatus;
use App\Models\FiscalYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OpenFiscalYear
{
    /**
     * Buka tahun anggaran beserta 12 tenggat bulanan.
     * Tenggat bulan M jatuh pada `default_deadline_day` bulan berikutnya (Desember → Januari tahun berikut).
     *
     * @param  array<int, array{stage_no: int, start_month: int, end_month: int, label: string}>  $stages
     */
    public function handle(int $year, int $deadlineDay, array $stages = []): FiscalYear
    {
        return DB::transaction(function () use ($year, $deadlineDay, $stages) {
            $fiscalYear = FiscalYear::query()->create([
                'year' => $year,
                'status' => FiscalYearStatus::Open,
                'default_deadline_day' => $deadlineDay,
            ]);

            foreach (range(1, 12) as $month) {
                $fiscalYear->deadlines()->create([
                    'month' => $month,
                    'due_date' => Carbon::create($year, $month, 1)->addMonth()->day($deadlineDay)->toDateString(),
                ]);
            }

            foreach ($stages as $stage) {
                $fiscalYear->stages()->create($stage);
            }

            return $fiscalYear;
        });
    }
}
