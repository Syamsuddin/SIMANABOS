<?php

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\MonthlyReport;
use App\Models\PeriodicReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Mengunggah PDF baru ke laporan bulanan atau periodik sebagai dokumen versi 1 (docs/07 §Dokumen). */
class UploadDocument
{
    /**
     * @param  list<int>  $typeIds
     * @return array{document: Document, duplicate: bool}
     */
    public function __invoke(MonthlyReport|PeriodicReport $report, UploadedFile $file, User $user, array $typeIds): array
    {
        $stored = $this->store($report, $file);

        $document = DB::transaction(function () use ($report, $user, $typeIds, $stored) {
            $document = $report->documents()->create(['uploaded_by' => $user->id]);
            $version = $document->versions()->create([...$stored, 'version_no' => 1, 'uploaded_by' => $user->id]);
            $document->forceFill(['current_version_id' => $version->id])->save();
            $document->types()->sync($typeIds);

            return $document;
        });

        return ['document' => $document, 'duplicate' => $this->isDuplicate($report, $stored['sha256'])];
    }

    /**
     * Simpan berkas ke disk private dengan nama UUID; isi PDF tidak pernah dibuka atau di-parse.
     *
     * @return array{storage_path: string, original_name: string, size_bytes: int, sha256: string}
     */
    public function store(MonthlyReport|PeriodicReport $report, UploadedFile $file): array
    {
        $report->loadMissing(['school', 'fiscalYear']);
        $directory = 'documents/'.$report->fiscalYear->year.'/'.$report->school->npsn;
        $path = $file->storeAs($directory, Str::uuid()->toString().'.pdf', 'private');

        return [
            'storage_path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
            'size_bytes' => (int) $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
        ];
    }

    /** Hash sama sudah ada pada laporan lain milik sekolah yang sama: hanya peringatan (docs/23 F-06). */
    public function isDuplicate(MonthlyReport|PeriodicReport $report, string $sha256): bool
    {
        if ($report instanceof PeriodicReport) {
            return false;
        }

        return DocumentVersion::query()->where('sha256', $sha256)
            ->whereHas('document', fn ($q) => $q->where('documentable_type', $report->getMorphClass())
                ->whereIn('documentable_id', MonthlyReport::query()->withoutGlobalScopes()
                    ->where('school_id', $report->school_id)->where('id', '!=', $report->id)->select('id')))
            ->exists();
    }
}
