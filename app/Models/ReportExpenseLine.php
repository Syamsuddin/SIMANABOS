<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['monthly_report_id', 'expense_type_id', 'gross_amount', 'refund_amount'])]
class ReportExpenseLine extends Model
{
    public function monthlyReport(): BelongsTo
    {
        return $this->belongsTo(MonthlyReport::class)->withoutGlobalScopes();
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }
}
