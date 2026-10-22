<?php

namespace App\Models;

use App\Enums\OfficialRole;
use App\Models\Scopes\SchoolScope;
use Database\Factories\SchoolOfficialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['school_id', 'role', 'name', 'nip', 'started_at', 'ended_at', 'approved_by', 'approved_at'])]
class SchoolOfficial extends Model
{
    /** @use HasFactory<SchoolOfficialFactory> */
    use HasFactory, LogsActivity;

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'role' => OfficialRole::class,
            'started_at' => 'date',
            'ended_at' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['school_id', 'role', 'name', 'nip', 'started_at', 'ended_at', 'approved_by', 'approved_at'])
            ->logOnlyDirty();
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** Usulan yang belum disahkan admin. */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('approved_at');
    }

    /** Pengelola sah yang sedang menjabat. */
    public function scopeActive(Builder $query): void
    {
        $query->whereNotNull('approved_at')->whereNull('ended_at');
    }

    public function isPending(): bool
    {
        return $this->approved_at === null;
    }
}
