<?php

namespace App\Http\Controllers\Admin;

use App\Actions\FiscalYears\CloseFiscalYear;
use App\Actions\FiscalYears\OpenFiscalYear;
use App\Enums\FiscalYearStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CloseFiscalYearRequest;
use App\Http\Requests\Admin\OpenFiscalYearRequest;
use App\Http\Requests\Admin\StoreStageRequest;
use App\Http\Requests\Admin\UpdateDeadlinesRequest;
use App\Models\AppSetting;
use App\Models\FiscalYear;
use App\Models\FiscalYearStage;
use App\Models\MonthlyReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class FiscalYearController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', FiscalYear::class);

        return view('admin.fiscal-years.index', ['fiscalYears' => FiscalYear::query()->orderByDesc('year')->get()]);
    }

    public function create(): View
    {
        Gate::authorize('create', FiscalYear::class);

        return view('admin.fiscal-years.create', ['defaultYear' => (int) now()->year, 'defaultDeadlineDay' => AppSetting::defaultDeadlineDay()]);
    }

    public function store(OpenFiscalYearRequest $request, OpenFiscalYear $openFiscalYear): RedirectResponse
    {
        $fiscalYear = $openFiscalYear->handle((int) $request->validated('year'), (int) $request->validated('default_deadline_day'));

        return redirect()->route('admin.fiscal-years.show', $fiscalYear)->with('success', 'Tahun anggaran dibuka.');
    }

    public function show(FiscalYear $fiscalYear): View
    {
        Gate::authorize('view', $fiscalYear);

        return view('admin.fiscal-years.show', [
            'fiscalYear' => $fiscalYear->load(['stages', 'deadlines']),
            'canEdit' => request()->user()->can('update', $fiscalYear),
            'canClose' => request()->user()->can('close', $fiscalYear),
            'unfinishedDecember' => $fiscalYear->status === FiscalYearStatus::Open
                ? MonthlyReport::query()->withoutGlobalScopes()->where('fiscal_year_id', $fiscalYear->id)->where('month', 12)
                    ->where('status', '!=', ReportStatus::SelesaiDiperiksa->value)->count()
                : 0,
        ]);
    }

    public function close(CloseFiscalYearRequest $request, FiscalYear $fiscalYear, CloseFiscalYear $closeFiscalYear): RedirectResponse
    {
        $count = $closeFiscalYear($fiscalYear, $request->user(), $request->validated('confirm_year'));

        return redirect()->route('admin.fiscal-years.show', $fiscalYear)
            ->with('success', "Tahun anggaran ditutup; {$count} saldo awal tahun berikut ditetapkan.");
    }

    public function updateDeadlines(UpdateDeadlinesRequest $request, FiscalYear $fiscalYear): RedirectResponse
    {
        foreach ($request->validated('due_dates') as $month => $date) {
            $fiscalYear->deadlines()->where('month', (int) $month)->update(['due_date' => $date]);
        }

        return back()->with('success', 'Tenggat tersimpan.');
    }

    public function storeStage(StoreStageRequest $request, FiscalYear $fiscalYear): RedirectResponse
    {
        $fiscalYear->stages()->create($request->validated());

        return back()->with('success', 'Tahap tersimpan.');
    }

    public function destroyStage(FiscalYear $fiscalYear, FiscalYearStage $stage): RedirectResponse
    {
        Gate::authorize('update', $fiscalYear);
        abort_unless($stage->fiscal_year_id === $fiscalYear->id, 404);

        $stage->delete();

        return back()->with('success', 'Tahap dihapus.');
    }
}
