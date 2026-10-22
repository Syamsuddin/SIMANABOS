<?php

namespace App\Http\Controllers\School;

use App\Actions\Documents\DeleteDocument;
use App\Actions\Documents\ReplaceDocument;
use App\Actions\Documents\TagDocument;
use App\Actions\Documents\UploadDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\ReplaceDocumentRequest;
use App\Http\Requests\School\TagDocumentRequest;
use App\Http\Requests\School\UploadDocumentRequest;
use App\Models\Document;
use App\Models\MonthlyReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DocumentController extends Controller
{
    public function store(UploadDocumentRequest $request, MonthlyReport $report, UploadDocument $upload): RedirectResponse
    {
        $result = $upload($report, $request->file('file'), $request->user(), $request->typeIds());

        return $this->back($report, 'Dokumen terunggah.', $result['duplicate']);
    }

    public function replace(ReplaceDocumentRequest $request, MonthlyReport $report, Document $document, ReplaceDocument $replace): RedirectResponse
    {
        $result = $replace($report, $document, $request->file('file'), $request->user(), $request->reason());

        return $this->back($report, 'Berkas diganti; versi lama tetap tersimpan.', $result['duplicate']);
    }

    public function tag(TagDocumentRequest $request, MonthlyReport $report, Document $document, TagDocument $tag): RedirectResponse
    {
        $tag($document, $request->typeIds());

        return $this->back($report, 'Jenis dokumen diperbarui.');
    }

    public function destroy(MonthlyReport $report, Document $document, DeleteDocument $delete): RedirectResponse
    {
        Gate::authorize('delete', $document);
        $delete($document);

        return $this->back($report, 'Dokumen dihapus.');
    }

    private function back(MonthlyReport $report, string $message, bool $duplicate = false): RedirectResponse
    {
        $redirect = redirect()->route('school.reports.edit', $report)->with('success', $message);

        return $duplicate
            ? $redirect->with('warning', 'Berkas dengan isi yang sama sudah pernah diunggah pada laporan lain sekolah ini.')
            : $redirect;
    }
}
