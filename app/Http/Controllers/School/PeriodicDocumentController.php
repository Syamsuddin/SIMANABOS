<?php

namespace App\Http\Controllers\School;

use App\Actions\Documents\DeleteDocument;
use App\Actions\Documents\ReplaceDocument;
use App\Actions\Documents\UploadDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\ReplaceDocumentRequest;
use App\Http\Requests\School\UploadPeriodicDocumentRequest;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\PeriodicReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PeriodicDocumentController extends Controller
{
    public function store(UploadPeriodicDocumentRequest $request, PeriodicReport $periodic, UploadDocument $upload): RedirectResponse
    {
        $typeId = DocumentType::query()->where('code', $periodic->kind->documentTypeCode())->value('id');
        $upload($periodic, $request->file('file'), $request->user(), [$typeId]);

        return $this->back($periodic, 'Dokumen terunggah.');
    }

    public function replace(ReplaceDocumentRequest $request, PeriodicReport $periodic, Document $document, ReplaceDocument $replace): RedirectResponse
    {
        $replace($periodic, $document, $request->file('file'), $request->user(), $request->reason());

        return $this->back($periodic, 'Berkas diganti; versi lama tetap tersimpan.');
    }

    public function destroy(PeriodicReport $periodic, Document $document, DeleteDocument $delete): RedirectResponse
    {
        Gate::authorize('delete', $document);
        $delete($document);

        return $this->back($periodic, 'Dokumen dihapus.');
    }

    private function back(PeriodicReport $periodic, string $message): RedirectResponse
    {
        return redirect()->route('school.periodic.index', ['tahun' => $periodic->fiscalYear->year])->with('success', $message);
    }
}
