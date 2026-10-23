<?php

namespace App\Http\Controllers\School;

use App\Actions\Reports\SaveReportFigures;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\ReportFiguresRequest;
use App\Models\AppSetting;
use App\Models\DocumentType;
use App\Models\FiscalYear;
use App\Models\MonthlyReport;
use App\Services\DocumentCompletenessChecker;
use App\Services\RemainingBudgetCalculator;
use App\Services\ReportAuditTrail;
use App\Services\ReportBalanceCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', MonthlyReport::class);
        abort_if($request->user()->school_id === null, 404);
        $fiscalYear = FiscalYear::resolve($request->integer('tahun') ?: null);

        $groups = $fiscalYear === null ? collect() : MonthlyReport::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->with('fundSource')->orderBy('fund_source_id')->orderBy('month')->get()
            ->groupBy('fund_source_id');

        return view('school.reports.index', [
            'fiscalYear' => $fiscalYear,
            'fiscalYears' => FiscalYear::query()->orderByDesc('year')->get(),
            'groups' => $groups,
        ]);
    }

    public function edit(MonthlyReport $report, ReportBalanceCalculator $calculator, RemainingBudgetCalculator $remaining, DocumentCompletenessChecker $completeness, ReportAuditTrail $audit): View
    {
        Gate::authorize('view', $report);
        $report->load(['lines', 'adjustments', 'fundSource', 'fiscalYear', 'reviewNotes.reviewer', 'statusHistories.actor']);

        $lines = $report->lines->keyBy('expense_type_id');
        $expenseTypes = $report->fiscalYear->expenseTypes()
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $lines->keys()))
            ->orderBy('id')->get();

        return view('school.reports.edit', [
            'report' => $report,
            'expenseTypes' => $expenseTypes,
            'lines' => $lines,
            'balance' => $calculator->forReport($report),
            'remaining' => $remaining->forReport($report),
            'canEdit' => request()->user()->can('update', $report),
            'canSubmit' => request()->user()->can('submit', $report),
            'canWithdraw' => request()->user()->can('withdraw', $report),
            'canApprove' => AppSetting::requiresPrincipalApproval() && request()->user()->can('approve', $report) && $report->isEditable(),
            'canRespond' => request()->user()->can('respond', $report),
            'principalRequired' => AppSetting::requiresPrincipalApproval(),
            'documents' => $report->documents()->with(['types', 'currentVersion', 'versions'])->orderBy('id')->get(),
            'documentTypes' => DocumentType::query()->where('scope', 'monthly')->orderBy('id')->get(),
            'completeness' => $completeness->forReport($report),
            'auditTrail' => request()->user()->can('viewAudit', $report) ? $audit->forReport($report) : null,
        ]);
    }

    public function update(ReportFiguresRequest $request, MonthlyReport $report, SaveReportFigures $save): RedirectResponse
    {
        $save($report, $request->figures(), $request->expenseLines(), $request->integer('lock_version'));

        return redirect()->route('school.reports.edit', $report)->with('success', 'Draf laporan tersimpan.');
    }
}
