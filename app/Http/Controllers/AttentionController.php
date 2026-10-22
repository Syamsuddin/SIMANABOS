<?php

namespace App\Http\Controllers;

use App\Enums\AttentionTrigger;
use App\Http\Requests\AttentionFilterRequest;
use App\Models\District;
use App\Models\FiscalYear;
use App\Models\MonthlyReport;
use App\Services\AttentionListBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class AttentionController extends Controller
{
    public function index(AttentionFilterRequest $request, AttentionListBuilder $builder): View
    {
        Gate::authorize('viewAny', MonthlyReport::class);
        abort_if($request->user()->isSchoolUser(), 404);

        $fiscalYear = FiscalYear::resolve($request->integer('tahun') ?: null);

        return view('attention.index', [
            'fiscalYear' => $fiscalYear,
            'fiscalYears' => FiscalYear::query()->orderByDesc('year')->get(),
            'districts' => District::query()->orderBy('name')->pluck('name', 'id')->all(),
            'triggers' => collect(AttentionTrigger::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all(),
            'rows' => $fiscalYear === null ? collect() : $builder->build($fiscalYear, [
                'trigger' => $request->query('pemicu') ?: null,
                'level' => $request->query('jenjang') ?: null,
                'district_id' => $request->integer('kecamatan') ?: null,
            ]),
        ]);
    }
}
