<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Budget\SaveAllocation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AllocationRequest;
use App\Models\Allocation;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\School;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AllocationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Allocation::class);

        $fiscalYear = FiscalYear::resolve($request->integer('tahun') ?: null);
        $fundSources = FundSource::query()->where('is_active', true)->orderBy('name')->get();
        $fundSource = $fundSources->firstWhere('id', $request->integer('sumber_dana')) ?? $fundSources->first();

        $allocations = $fiscalYear && $fundSource
            ? Allocation::query()->where('fiscal_year_id', $fiscalYear->id)->where('fund_source_id', $fundSource->id)->get()->keyBy('school_id')
            : collect();

        return view('admin.allocations.index', [
            'fiscalYears' => FiscalYear::query()->orderByDesc('year')->get(),
            'fiscalYear' => $fiscalYear,
            'fundSources' => $fundSources,
            'fundSource' => $fundSource,
            'schools' => School::query()->where('is_active', true)->orderBy('name')->get(),
            'allocations' => $allocations,
            'canEdit' => $fiscalYear !== null && $request->user()->can('manage', [Allocation::class, $fiscalYear]),
        ]);
    }

    public function store(AllocationRequest $request, SaveAllocation $saveAllocation): RedirectResponse
    {
        $fiscalYear = FiscalYear::query()->findOrFail($request->integer('fiscal_year_id'));
        $fundSource = FundSource::query()->findOrFail($request->integer('fund_source_id'));
        $schools = School::query()->where('is_active', true)->get()->keyBy('id');

        $saved = 0;
        foreach ($request->validated('rows', []) as $schoolId => $row) {
            if ($row['amount'] === null || ! $schools->has($schoolId)) {
                continue;
            }
            $saveAllocation($schools[$schoolId], $fiscalYear, $fundSource, $row['amount'], (int) $row['active_from_month']);
            $saved++;
        }

        return redirect()
            ->route('admin.allocations.index', ['tahun' => $fiscalYear->year, 'sumber_dana' => $fundSource->id])
            ->with('success', $saved > 0 ? "Pagu {$saved} sekolah tersimpan dan laporan bulanan disiapkan." : 'Tidak ada pagu yang diisi.');
    }
}
