<?php

namespace App\Actions\Documents;

use App\Models\Document;

/**
 * Soft delete dokumen sebelum pengajuan pertama. Versi dan berkas fisik sengaja dipertahankan
 * (docs/20 butir 5: tidak ada hapus permanen).
 */
class DeleteDocument
{
    public function __invoke(Document $document): void
    {
        $document->delete();
    }
}
