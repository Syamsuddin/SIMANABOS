<?php

namespace App\Models;

use App\Enums\AdjustmentKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['monthly_report_id', 'kind', 'expense_type_id', 'origin_fiscal_year_id', 'origin_month', 'amount', 'reason'])]
class ReportAdjustment extends Model
{
    protected function casts(): array
    {
        return ['kind' => AdjustmentKind::class, 'origin_month' => 'integer'];
    }

    public function monthlyReport(): BelongsTo
    {
        return $this->belongsTo(MonthlyReport::class)->withoutGlobalScopes();
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }
}
