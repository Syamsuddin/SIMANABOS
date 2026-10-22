<?php

namespace App\Http\Controllers\School;

use App\Actions\Reports\ApproveByPrincipal;
use App\Actions\Reports\RespondToNote;
use App\Actions\Reports\SubmitReport;
use App\Actions\Reports\WithdrawReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\RespondNoteRequest;
use App\Models\MonthlyReport;
use App\Models\ReviewNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportSubmissionController extends Controller
{
    public function submit(Request $request, MonthlyReport $report, SubmitReport $submit): RedirectResponse
    {
        Gate::authorize('submit', $report);
        $submit($report, $request->user());

        return redirect()->route('school.reports.edit', $report)->with('success', 'Laporan diajukan.');
    }

    public function withdraw(Request $request, MonthlyReport $report, WithdrawReport $withdraw): RedirectResponse
    {
        Gate::authorize('withdraw', $report);
        $withdraw($report, $request->user());

        return redirect()->route('school.reports.edit', $report)->with('success', 'Laporan ditarik kembali menjadi draf.');
    }

    public function approve(Request $request, MonthlyReport $report, ApproveByPrincipal $approve): RedirectResponse
    {
        Gate::authorize('approve', $report);
        $approve($report, $request->user());

        return redirect()->route('school.reports.edit', $report)->with('success', 'Laporan disetujui.');
    }

    public function respond(RespondNoteRequest $request, MonthlyReport $report, ReviewNote $note, RespondToNote $respond): RedirectResponse
    {
        abort_unless($note->monthly_report_id === $report->id, 404);
        $respond($note, $request->validated('response_body'));

        return redirect()->route('school.reports.edit', $report)->with('success', 'Tanggapan tersimpan.');
    }
}
