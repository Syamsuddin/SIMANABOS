<?php

namespace App\Models;

use App\Enums\SchoolLevel;
use App\Enums\SchoolStatus;
use App\Models\Scopes\SchoolScope;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['npsn', 'name', 'level', 'status', 'district_id', 'address', 'phone', 'is_active'])]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new SchoolScope('id'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'level' => SchoolLevel::class,
            'status' => SchoolStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function officials(): HasMany
    {
        return $this->hasMany(SchoolOfficial::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
