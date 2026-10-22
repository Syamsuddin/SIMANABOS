<?php

namespace App\Services;

use App\Enums\ReportKind;
use App\Models\DocumentRequirement;
use App\Models\DocumentType;
use App\Models\MonthlyReport;
use Illuminate\Support\Collection;

/**
 * Menghitung kelengkapan dokumen wajib laporan bulanan menurut `document_requirements` (docs/06 §C).
 * Satu berkas yang ditandai beberapa jenis memenuhi semuanya. Aplikasi tidak menebak isi aturan.
 */
class DocumentCompletenessChecker
{
    /**
     * @return array{required: Collection<int, DocumentType>, missing: Collection<int, DocumentType>, complete: bool}
     */
    public function forReport(MonthlyReport $report): array
    {
        $report->loadMissing('school');

        $required = DocumentRequirement::query()
            ->where('fiscal_year_id', $report->fiscal_year_id)
            ->where('report_kind', ReportKind::Monthly->value)
            ->where('is_required', true)
            ->whereIn('school_status', ['all', $report->school->status->value])
            ->where(fn ($q) => $q->whereNull('fund_source_id')->orWhere('fund_source_id', $report->fund_source_id))
            ->with('documentType')->get()
            ->pluck('documentType')->unique('id')->sortBy('id')->values();

        $tagged = $report->documents()->whereHas('currentVersion')->with('types')->get()
            ->flatMap(fn ($document) => $document->types->pluck('id'))->unique();

        $missing = $required->reject(fn (DocumentType $type) => $tagged->contains($type->id))->values();

        return ['required' => $required, 'missing' => $missing, 'complete' => $missing->isEmpty()];
    }

    /** @return list<string> pesan galat untuk ReportSubmissionValidator. */
    public function errors(MonthlyReport $report): array
    {
        return $this->forReport($report)['missing']
            ->map(fn (DocumentType $type) => 'Dokumen wajib belum diunggah: '.$type->name.'.')->all();
    }
}
