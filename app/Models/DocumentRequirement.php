<?php

namespace App\Models;

use App\Enums\ReportKind;
use App\Enums\SchoolStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['fiscal_year_id', 'document_type_id', 'report_kind', 'school_status', 'fund_source_id', 'is_required'])]
class DocumentRequirement extends Model
{
    protected function casts(): array
    {
        return [
            'report_kind' => ReportKind::class,
            'is_required' => 'boolean',
        ];
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function fundSource(): BelongsTo
    {
        return $this->belongsTo(FundSource::class);
    }

    /** 'all' atau nilai SchoolStatus. */
    public function statusLabel(): string
    {
        return $this->school_status === 'all' ? 'Semua status' : SchoolStatus::from($this->school_status)->label();
    }
}
