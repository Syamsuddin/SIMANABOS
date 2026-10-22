<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['monthly_report_id', 'reviewer_id', 'target_type', 'target_key', 'body', 'response_body', 'responded_at', 'resolved_at'])]
class ReviewNote extends Model
{
    public const TARGETS = ['field', 'document_type', 'general'];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(MonthlyReport::class, 'monthly_report_id')->withoutGlobalScopes();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function isOpen(): bool
    {
        return $this->resolved_at === null;
    }

    /** Catatan terbuka yang belum ditanggapi operator (syarat ajukan ulang, docs/06 §D). */
    public function needsResponse(): bool
    {
        return $this->isOpen() && $this->responded_at === null;
    }
}
