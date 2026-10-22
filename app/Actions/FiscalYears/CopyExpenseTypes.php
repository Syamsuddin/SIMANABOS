<?php

namespace App\Actions\FiscalYears;

use App\Models\FiscalYear;
use Illuminate\Support\Facades\DB;

class CopyExpenseTypes
{
    /**
     * Salin jenis belanja dari tahun anggaran sebelumnya; kode yang sudah ada di tahun tujuan dilewati.
     *
     * @return int jumlah jenis belanja yang disalin
     */
    public function handle(FiscalYear $target): int
    {
        $source = FiscalYear::query()->where('year', '<', $target->year)->orderByDesc('year')->first();
        if (! $source) {
            return 0;
        }

        return DB::transaction(function () use ($source, $target) {
            $existing = $target->expenseTypes()->pluck('code')->all();
            $copied = 0;

            foreach ($source->expenseTypes as $type) {
                if (in_array($type->code, $existing, true)) {
                    continue;
                }
                $target->expenseTypes()->create($type->only(['code', 'arkas_account_code', 'name', 'sort_order', 'is_active']));
                $copied++;
            }

            return $copied;
        });
    }
}
