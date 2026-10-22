<?php

namespace App\Models;

use App\Enums\ReportStatus;
use App\Models\Scopes\SchoolScope;
use Database\Factories\MonthlyReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

// `status`, `opening_*`, dan `computed_*` sengaja tidak fillable: status hanya lewat ReportStateMachine (F-07),
// saldo awal dan cache hanya ditulis SaveReportFigures.
#[Fillable([
    'school_id', 'fiscal_year_id', 'fund_source_id', 'month',
    'is_nil', 'receipt_bos', 'receipt_other', 'receipt_other_note', 'treasury_deposit',
    'tax_withheld', 'tax_remitted', 'bank_balance_bku', 'cash_balance_bku', 'difference_note',
])]
class MonthlyReport extends Model
{
    /** @use HasFactory<MonthlyReportFactory> */
    use HasFactory, LogsActivity;

    protected $attributes = ['status' => 'draf'];

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope);
    }

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'month' => 'integer',
            'is_nil' => 'boolean',
            'is_late' => 'boolean',
            'needs_rereview' => 'boolean',
            'identity_snapshot' => 'array',
            'principal_approved_at' => 'datetime',
            'submitted_at' => 'datetime',
            'review_started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'is_nil', 'receipt_bos', 'receipt_other', 'treasury_deposit', 'tax_withheld', 'tax_remitted', 'bank_balance_bku', 'cash_balance_bku', 'computed_ending_fund', 'computed_ending_tax', 'computed_difference', 'is_late', 'needs_rereview'])
            ->logOnlyDirty();
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class)->withoutGlobalScopes();
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function fundSource(): BelongsTo
    {
        return $this->belongsTo(FundSource::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ReportExpenseLine::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(ReportAdjustment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class)->orderBy('id');
    }

    public function reviewNotes(): HasMany
    {
        return $this->hasMany(ReviewNote::class)->orderBy('id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /** Hanya `draf` dan `perlu_perbaikan` yang angkanya boleh diubah operator (docs/06 §D). */
    public function isEditable(): bool
    {
        return in_array($this->status, [ReportStatus::Draf, ReportStatus::PerluPerbaikan], true);
    }
}
