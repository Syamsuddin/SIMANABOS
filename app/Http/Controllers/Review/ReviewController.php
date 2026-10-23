<?php

namespace App\Http\Controllers\Review;

use App\Actions\Reports\AddReviewNote;
use App\Actions\Reports\CompleteReview;
use App\Actions\Reports\ReleaseReview;
use App\Actions\Reports\RequestRevision;
use App\Actions\Reports\ResolveNote;
use App\Actions\Reports\StartReview;
use App\Actions\Reports\UnlockReport;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreNoteRequest;
use App\Http\Requests\Review\UnlockReportRequest;
use App\Models\FiscalYear;
use App\Models\MonthlyReport;
use App\Models\ReviewNote;
use App\Services\DocumentCompletenessChecker;
use App\Services\ReportAuditTrail;
use App\Services\ReportBalanceCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', MonthlyReport::class);
        abort_if($request->user()->isSchoolUser(), 404);

        $fiscalYear = FiscalYear::resolve($request->integer('tahun') ?: null);
        $filter = $request->query('status', 'diajukan');
        $statuses = match ($filter) {
            'dalam_pemeriksaan' => [ReportStatus::DalamPemeriksaan],
            'perlu_perbaikan' => [ReportStatus::PerluPerbaikan],
            'selesai_diperiksa' => [ReportStatus::SelesaiDiperiksa],
            default => [ReportStatus::Diajukan],
        };

        $reports = $fiscalYear === null ? collect() : MonthlyReport::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->whereIn('status', array_map(fn ($s) => $s->value, $statuses))
            ->with(['school', 'fundSource', 'reviewer'])
            ->orderBy('submitted_at')->orderBy('id')->get();

        return view('review.index', [
            'fiscalYear' => $fiscalYear,
            'fiscalYears' => FiscalYear::query()->orderByDesc('year')->get(),
            'filter' => $filter,
            'reports' => $reports,
        ]);
    }

    public function show(MonthlyReport $report, ReportBalanceCalculator $calculator, DocumentCompletenessChecker $completeness, ReportAuditTrail $audit): View
    {
        Gate::authorize('view', $report);
        abort_if(request()->user()->isSchoolUser(), 404);
        $report->load(['school', 'fundSource', 'fiscalYear', 'lines' => fn ($q) => $q->orderBy('expense_type_id')->with('expenseType'), 'adjustments', 'reviewer', 'statusHistories.actor', 'reviewNotes.reviewer']);

        return view('review.show', [
            'report' => $report,
            'balance' => $calculator->forReport($report),
            'completeness' => $completeness->forReport($report),
            'documents' => $report->documents()->with(['types', 'currentVersion'])->orderBy('id')->get(),
            'canClaim' => request()->user()->can('claim', $report),
            'canReview' => request()->user()->can('review', $report),
            'canUnlock' => request()->user()->can('unlock', $report),
            'auditTrail' => request()->user()->can('viewAudit', $report) ? $audit->forReport($report) : null,
        ]);
    }

    public function claim(Request $request, MonthlyReport $report, StartReview $start): RedirectResponse
    {
        Gate::authorize('claim', $report);
        $start($report, $request->user());

        return redirect()->route('review.reports.show', $report)->with('success', 'Laporan diambil untuk diperiksa.');
    }

    public function release(Request $request, MonthlyReport $report, ReleaseReview $release): RedirectResponse
    {
        Gate::authorize('review', $report);
        $release($report, $request->user());

        return redirect()->route('review.reports.index')->with('success', 'Laporan dikembalikan ke antrean.');
    }

    public function storeNote(StoreNoteRequest $request, MonthlyReport $report, AddReviewNote $add): RedirectResponse
    {
        $add($report, $request->user(), $request->validated('target_type'), $request->validated('target_key'), $request->validated('body'));

        return redirect()->route('review.reports.show', $report)->with('success', 'Catatan ditambahkan.');
    }

    public function resolveNote(Request $request, MonthlyReport $report, ReviewNote $note, ResolveNote $resolve): RedirectResponse
    {
        Gate::authorize('review', $report);
        abort_unless($note->monthly_report_id === $report->id, 404);
        $resolve($note, $request->user());

        return redirect()->route('review.reports.show', $report)->with('success', 'Catatan ditandai selesai.');
    }

    public function requestRevision(Request $request, MonthlyReport $report, RequestRevision $revise): RedirectResponse
    {
        Gate::authorize('review', $report);
        $revise($report, $request->user());

        return redirect()->route('review.reports.index')->with('success', 'Laporan dikembalikan ke sekolah untuk diperbaiki.');
    }

    public function complete(Request $request, MonthlyReport $report, CompleteReview $complete): RedirectResponse
    {
        Gate::authorize('review', $report);
        $complete($report, $request->user());

        return redirect()->route('review.reports.show', $report)->with('success', 'Laporan dinyatakan selesai diperiksa.');
    }

    public function unlock(UnlockReportRequest $request, MonthlyReport $report, UnlockReport $unlock): RedirectResponse
    {
        $unlock($report, $request->user(), $request->validated('reason'));

        return redirect()->route('review.reports.show', $report)->with('success', 'Kunci laporan dibuka; laporan dikembalikan ke sekolah.');
    }
}
