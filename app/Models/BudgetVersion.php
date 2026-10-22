<?php

namespace App\Models;

use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['school_id', 'fiscal_year_id', 'fund_source_id', 'version_no', 'effective_date', 'reason'])]
class BudgetVersion extends Model
{
    use LogsActivity;

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope);
    }

    protected function casts(): array
    {
        return [
            'version_no' => 'integer',
            'effective_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['school_id', 'fiscal_year_id', 'fund_source_id', 'version_no', 'effective_date', 'reason'])
            ->logOnlyDirty();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
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
}
