<?php

namespace App\Http\Controllers\School;

use App\Actions\Budget\BudgetSummary;
use App\Actions\Budget\SaveBudgetVersion;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\BudgetVersionRequest;
use App\Models\Allocation;
use App\Models\BudgetVersion;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\School;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BudgetController extends Controller
{
    public function index(Request $request, BudgetSummary $summary): View
    {
        Gate::authorize('viewAny', BudgetVersion::class);
        $school = $this->school($request);
        $fiscalYear = FiscalYear::resolve($request->integer('tahun') ?: null);

        $sections = collect();
        if ($fiscalYear) {
            $allocations = Allocation::query()->where('fiscal_year_id', $fiscalYear->id)->with('fundSource')->get();
            $sections = $allocations->map(function (Allocation $allocation) use ($fiscalYear, $summary) {
                $versions = BudgetVersion::query()
                    ->where('fiscal_year_id', $fiscalYear->id)
                    ->where('fund_source_id', $allocation->fund_source_id)
                    ->with('lines.expenseType')->orderBy('version_no')->get();
                $latest = $versions->last();

                return [
                    'allocation' => $allocation,
                    'versions' => $versions->map(fn (BudgetVersion $v) => ['version' => $v, 'total' => $summary->total($v)]),
                    'warning' => $latest ? $summary->ceilingWarning($latest) : null,
                ];
            });
        }

        return view('school.budget.index', [
            'school' => $school,
            'fiscalYear' => $fiscalYear,
            'fiscalYears' => FiscalYear::query()->orderByDesc('year')->get(),
            'sections' => $sections,
            'canEdit' => $fiscalYear !== null && $request->user()->can('create', [BudgetVersion::class, $fiscalYear]),
        ]);
    }

    public function create(Request $request): View
    {
        $fiscalYear = FiscalYear::resolve($request->integer('tahun') ?: null);
        abort_if($fiscalYear === null, 404);
        Gate::authorize('create', [BudgetVersion::class, $fiscalYear]);

        $fundSource = FundSource::query()->findOrFail($request->integer('sumber_dana'));
        abort_unless(Allocation::query()->where('fiscal_year_id', $fiscalYear->id)->where('fund_source_id', $fundSource->id)->exists(), 404);

        $latest = BudgetVersion::query()
            ->where('fiscal_year_id', $fiscalYear->id)->where('fund_source_id', $fundSource->id)
            ->with('lines')->orderByDesc('version_no')->first();

        return view('school.budget.form', [
            'fiscalYear' => $fiscalYear,
            'fundSource' => $fundSource,
            'expenseTypes' => $fiscalYear->expenseTypes()->where('is_active', true)->get(),
            'latest' => $latest,
            'previous' => $latest?->lines->pluck('amount', 'expense_type_id'),
        ]);
    }

    public function store(BudgetVersionRequest $request, SaveBudgetVersion $saveBudgetVersion, BudgetSummary $summary): RedirectResponse
    {
        $school = $this->school($request);
        $fiscalYear = FiscalYear::query()->findOrFail($request->integer('fiscal_year_id'));
        $fundSource = FundSource::query()->findOrFail($request->integer('fund_source_id'));

        $version = $saveBudgetVersion($school, $fiscalYear, $fundSource, $request->validated('effective_date'), $request->validated('reason'), $request->validated('lines'));

        $redirect = redirect()->route('school.budget.index', ['tahun' => $fiscalYear->year])
            ->with('success', "RKAS versi {$version->version_no} tersimpan.");

        if ($summary->ceilingWarning($version)) {
            $redirect->with('warning', 'Total anggaran RKAS melebihi pagu ditambah saldo awal. Periksa kembali angka Anda; data tetap tersimpan.');
        }

        return $redirect;
    }

    /** Akun tanpa `school_id` (peran dinas) tidak punya RKAS sekolah: 404. */
    private function school(Request $request): School
    {
        abort_if($request->user()->school_id === null, 404);

        return School::query()->findOrFail($request->user()->school_id);
    }
}
