<?php

namespace App\Models;

use App\Enums\FiscalYearStatus;
use Database\Factories\FiscalYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['year', 'status', 'default_deadline_day', 'closed_at', 'closed_by'])]
class FiscalYear extends Model
{
    /** @use HasFactory<FiscalYearFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'status' => FiscalYearStatus::class,
            'default_deadline_day' => 'integer',
            'closed_at' => 'datetime',
        ];
    }

    /** Tahun yang diminta, atau tahun terbuka terbaru bila tidak ada. */
    public static function resolve(?int $year): ?self
    {
        return $year
            ? static::query()->where('year', $year)->first()
            : (static::query()->where('status', FiscalYearStatus::Open)->orderByDesc('year')->first()
                ?? static::query()->orderByDesc('year')->first());
    }

    public function stages(): HasMany
    {
        return $this->hasMany(FiscalYearStage::class)->orderBy('stage_no');
    }

    public function deadlines(): HasMany
    {
        return $this->hasMany(MonthlyDeadline::class)->orderBy('month');
    }

    public function expenseTypes(): HasMany
    {
        return $this->hasMany(ExpenseType::class)->orderBy('sort_order')->orderBy('code');
    }

    public function documentRequirements(): HasMany
    {
        return $this->hasMany(DocumentRequirement::class);
    }
}
