<?php

namespace App\Actions\Documents;

use App\Models\Document;

/** Menandai jenis dokumen pada satu berkas (satu berkas boleh memuat beberapa jenis). */
class TagDocument
{
    /** @param  list<int>  $typeIds */
    public function __invoke(Document $document, array $typeIds): Document
    {
        $document->types()->sync($typeIds);

        return $document;
    }
}
