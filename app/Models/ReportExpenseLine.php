<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['monthly_report_id', 'expense_type_id', 'gross_amount', 'refund_amount'])]
class ReportExpenseLine extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['monthly_report_id', 'expense_type_id', 'gross_amount', 'refund_amount'])->logOnlyDirty();
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
