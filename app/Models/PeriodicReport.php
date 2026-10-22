<?php

namespace App\Models;

use App\Enums\PeriodicKind;
use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['school_id', 'fiscal_year_id', 'fund_source_id', 'kind', 'stage_no', 'reported_total_realization', 'reported_ending_balance', 'note'])]
class PeriodicReport extends Model
{
    use LogsActivity;

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope);
    }

    protected function casts(): array
    {
        return ['kind' => PeriodicKind::class, 'stage_no' => 'integer'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['kind', 'stage_no', 'reported_total_realization', 'reported_ending_balance'])->logOnlyDirty();
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

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
