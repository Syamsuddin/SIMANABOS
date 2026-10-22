<?php

namespace App\Models;

use App\Models\Scopes\SchoolScope;
use Database\Factories\AllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['school_id', 'fiscal_year_id', 'fund_source_id', 'amount', 'active_from_month'])]
class Allocation extends Model
{
    /** @use HasFactory<AllocationFactory> */
    use HasFactory, LogsActivity;

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope);
    }

    protected function casts(): array
    {
        return ['active_from_month' => 'integer'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['school_id', 'fiscal_year_id', 'fund_source_id', 'amount', 'active_from_month'])
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
}
