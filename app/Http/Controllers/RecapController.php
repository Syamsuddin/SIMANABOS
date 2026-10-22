<?php

namespace App\Http\Controllers;

use App\Enums\RecapPeriod;
use App\Http\Requests\RecapFilterRequest;
use App\Models\District;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\MonthlyReport;
use App\Services\RecapBuilder;
use App\Services\RecapXlsxWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RecapController extends Controller
{
    public function index(RecapFilterRequest $request, RecapBuilder $builder): View
    {
        [$fiscalYear, $period, $rows] = $this->resolve($request, $builder);

        return view('recap.index', [
            'fiscalYear' => $fiscalYear,
            'fiscalYears' => FiscalYear::query()->orderByDesc('year')->get(),
            'districts' => District::query()->orderBy('name')->pluck('name', 'id')->all(),
            'fundSources' => FundSource::query()->orderBy('name')->pluck('name', 'id')->all(),
            'periods' => collect(RecapPeriod::cases())->mapWithKeys(fn (RecapPeriod $p) => [$p->value => $p->label()])->all(),
            'period' => $period,
            'rows' => $rows,
            'totals' => $rows === null ? null : $builder->totals($rows),
        ]);
    }

    public function export(RecapFilterRequest $request, RecapBuilder $builder, RecapXlsxWriter $writer): BinaryFileResponse
    {
        [$fiscalYear, $period, $rows] = $this->resolve($request, $builder);
        abort_if($rows === null, 404);

        $title = "Rekap dana BOS {$period->label()} {$fiscalYear->year}";
        $path = tempnam(sys_get_temp_dir(), 'rekap');
        $writer->write($path, $title, $rows, $builder->totals($rows));

        return response()->download($path, Str::slug($title).'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /** @return array{0: ?FiscalYear, 1: RecapPeriod, 2: ?Collection} */
    private function resolve(RecapFilterRequest $request, RecapBuilder $builder): array
    {
        Gate::authorize('viewAny', MonthlyReport::class);
        abort_if($request->user()->isSchoolUser(), 404);

        $fiscalYear = FiscalYear::resolve($request->integer('tahun') ?: null);
        $period = RecapPeriod::tryFrom((string) $request->query('periode')) ?? RecapPeriod::Tahun;

        $rows = $fiscalYear === null ? null : $builder->bySchool($fiscalYear->id, $period, [
            'level' => $request->query('jenjang') ?: null,
            'district_id' => $request->integer('kecamatan') ?: null,
            'fund_source_id' => $request->integer('sumber_dana') ?: null,
        ]);

        return [$fiscalYear, $period, $rows];
    }
}
