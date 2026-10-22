<?php

namespace App\Actions\FiscalYears;

use App\Models\FiscalYear;
use Illuminate\Support\Facades\DB;

class CopyDocumentRequirements
{
    /**
     * Salin aturan dokumen dari tahun anggaran sebelumnya; kombinasi yang sudah ada dilewati.
     *
     * @return int jumlah aturan yang disalin
     */
    public function handle(FiscalYear $target): int
    {
        $source = FiscalYear::query()->where('year', '<', $target->year)->orderByDesc('year')->first();
        if (! $source) {
            return 0;
        }

        return DB::transaction(function () use ($source, $target) {
            $key = fn ($r) => implode('|', [$r->document_type_id, $r->report_kind->value, $r->school_status, $r->fund_source_id ?? 'all']);
            $existing = $target->documentRequirements()->get()->map($key)->all();
            $copied = 0;

            foreach ($source->documentRequirements as $requirement) {
                if (in_array($key($requirement), $existing, true)) {
                    continue;
                }
                $target->documentRequirements()->create($requirement->only(['document_type_id', 'report_kind', 'school_status', 'fund_source_id', 'is_required']));
                $copied++;
            }

            return $copied;
        });
    }
}
