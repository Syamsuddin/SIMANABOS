<?php

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\MonthlyReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/** Mengganti berkas: versi baru menjadi current, versi lama tetap tersimpan dan bisa diunduh. */
class ReplaceDocument
{
    public function __construct(private readonly UploadDocument $upload) {}

    /** @return array{document: Document, duplicate: bool} */
    public function __invoke(MonthlyReport $report, Document $document, UploadedFile $file, User $user, ?string $reason): array
    {
        $stored = $this->upload->store($report, $file);

        DB::transaction(function () use ($document, $user, $reason, $stored) {
            $locked = Document::query()->whereKey($document->id)->lockForUpdate()->first();
            $next = ((int) $locked->versions()->max('version_no')) + 1;
            $version = $locked->versions()->create([
                ...$stored, 'version_no' => $next, 'uploaded_by' => $user->id, 'replaced_reason' => $reason,
            ]);
            $locked->forceFill(['current_version_id' => $version->id])->save();
        });

        return ['document' => $document->refresh(), 'duplicate' => $this->upload->isDuplicate($report, $stored['sha256'])];
    }
}
