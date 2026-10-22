<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only: hanya ditulis ReportStateMachine, tanpa updated_at. */
#[Fillable(['monthly_report_id', 'from_status', 'to_status', 'actor_id', 'note'])]
class StatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'from_status' => ReportStatus::class,
            'to_status' => ReportStatus::class,
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
