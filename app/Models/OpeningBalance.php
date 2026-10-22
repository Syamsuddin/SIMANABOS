<?php

namespace App\Models;

use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['school_id', 'fiscal_year_id', 'fund_source_id', 'fund_amount', 'tax_amount', 'origin', 'verified_by', 'verified_at'])]
class OpeningBalance extends Model
{
    use LogsActivity;

    public const ORIGIN_MANUAL = 'manual';

    public const ORIGIN_CARRIED_OVER = 'carried_over';

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope);
    }

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['school_id', 'fiscal_year_id', 'fund_source_id', 'fund_amount', 'tax_amount', 'origin', 'verified_by', 'verified_at'])
            ->logOnlyDirty();
    }

    public function isManual(): bool
    {
        return $this->origin === self::ORIGIN_MANUAL;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Saldo awal manual yang belum diverifikasi menghalangi Januari dinyatakan selesai
     * (docs/06 §B). Dipakai sebagai guard transisi `selesai_diperiksa` di F-07.
     */
    public function blocksJanuaryCompletion(): bool
    {
        return $this->isManual() && ! $this->isVerified();
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

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
