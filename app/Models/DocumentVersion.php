<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only: versi tidak pernah diubah atau dihapus (docs/20 butir 5). */
#[Fillable(['document_id', 'version_no', 'storage_path', 'original_name', 'size_bytes', 'sha256', 'uploaded_by', 'replaced_reason'])]
class DocumentVersion extends Model
{
    protected function casts(): array
    {
        return ['version_no' => 'integer', 'size_bytes' => 'integer'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class)->withTrashed();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
