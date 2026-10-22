<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardFilterRequest;
use App\Models\District;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Services\ReportAggregator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index(DashboardFilterRequest $request, ReportAggregator $aggregator): View
    {
        Gate::authorize('viewAny', MonthlyReport::class);
        abort_if($request->user()->isSchoolUser(), 404);

        $fiscalYear = FiscalYear::resolve($request->integer('tahun') ?: null);
        $from = $request->integer('dari') ?: 1;
        $to = $request->integer('sampai') ?: 12;

        $summary = $fiscalYear === null ? null : $aggregator->summary($fiscalYear->id, $from, $to, [
            'level' => $request->query('jenjang') ?: null,
            'status' => $request->query('status') ?: null,
            'district_id' => $request->integer('kecamatan') ?: null,
            'fund_source_id' => $request->integer('sumber_dana') ?: null,
        ]);

        return view('dashboard.index', [
            'fiscalYear' => $fiscalYear,
            'fiscalYears' => FiscalYear::query()->orderByDesc('year')->get(),
            'districts' => District::query()->orderBy('name')->pluck('name', 'id')->all(),
            'fundSources' => FundSource::query()->orderBy('name')->pluck('name', 'id')->all(),
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
        ]);
    }
}
