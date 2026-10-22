<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    /** Unduh versi tertentu (default: current) lewat Policy; tidak ada URL publik ke berkas (docs/21). */
    public function __invoke(Document $document, ?int $version = null): StreamedResponse
    {
        Gate::authorize('view', $document);

        $model = $version === null
            ? $document->currentVersion
            : $document->versions()->where('version_no', $version)->first();
        abort_if($model === null, 404);

        return Storage::disk('private')->download($model->storage_path, $model->original_name, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
