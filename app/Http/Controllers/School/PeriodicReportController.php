<?php

namespace App\Http\Controllers\School;

use App\Actions\Budget\SavePeriodicReport;
use App\Enums\PeriodicKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\PeriodicReportRequest;
use App\Models\Allocation;
use App\Models\DocumentType;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\PeriodicReport;
use App\Models\School;
use App\Services\PeriodicComparator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PeriodicReportController extends Controller
{
    /** Sekolah: slot Tahap/Tahunan/Berita Acara per sumber dana. Dinas: daftar semua sekolah dengan selisih. */
    public function index(Request $request, PeriodicComparator $comparator): View
    {
        Gate::authorize('viewAny', PeriodicReport::class);
        $user = $request->user();
        $fiscalYear = FiscalYear::resolve($request->integer('tahun') ?: null);
        $stages = $fiscalYear?->stages ?? collect();
        $reports = $fiscalYear === null ? collect() : PeriodicReport::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->with(['school', 'fundSource', 'documents' => fn ($q) => $q->with(['currentVersion', 'versions'])->orderBy('id')])
            ->orderBy('school_id')->orderBy('fund_source_id')->orderBy('kind')->orderBy('stage_no')->get();
        $comparisons = $reports->mapWithKeys(fn (PeriodicReport $r) => [$r->id => $comparator->compare($r)]);

        $common = [
            'fiscalYear' => $fiscalYear,
            'fiscalYears' => FiscalYear::query()->orderByDesc('year')->get(),
            'comparisons' => $comparisons,
        ];

        if (! $user->isSchoolUser()) {
            return view('periodic.index', $common + ['reports' => $reports]);
        }

        $byKey = $reports->keyBy(fn (PeriodicReport $r) => $r->fund_source_id.'|'.$r->kind->value.'|'.$r->stage_no);
        $slots = collect();
        if ($fiscalYear) {
            $funds = Allocation::query()->where('fiscal_year_id', $fiscalYear->id)->with('fundSource')->get()->pluck('fundSource');
            foreach ($funds as $fund) {
                $defs = $stages->map(fn ($s) => [PeriodicKind::Stage, $s->stage_no, $s->label ?: 'Tahap '.$s->stage_no])->all();
                $defs[] = [PeriodicKind::Annual, 0, PeriodicKind::Annual->label()];
                $defs[] = [PeriodicKind::Minutes, 0, PeriodicKind::Minutes->label()];
                foreach ($defs as [$kind, $stageNo, $label]) {
                    $slots->push([
                        'fund' => $fund, 'kind' => $kind, 'stage_no' => $stageNo, 'label' => $label,
                        'report' => $byKey->get($fund->id.'|'.$kind->value.'|'.$stageNo),
                    ]);
                }
            }
        }

        return view('school.periodic.index', $common + [
            'slots' => $slots->groupBy(fn ($slot) => $slot['fund']->id),
            'canEdit' => $fiscalYear !== null && $user->can('save', [PeriodicReport::class, $fiscalYear]),
            'documentTypes' => DocumentType::query()->where('scope', 'periodic')->get()->keyBy('code'),
        ]);
    }

    public function update(PeriodicReportRequest $request, SavePeriodicReport $save): RedirectResponse
    {
        $school = School::query()->findOrFail($request->user()->school_id);
        $fiscalYear = FiscalYear::query()->findOrFail($request->integer('fiscal_year_id'));

        $save(
            $school, $fiscalYear, FundSource::query()->findOrFail($request->integer('fund_source_id')),
            PeriodicKind::from($request->validated('kind')), (int) $request->validated('stage_no'),
            $request->validated('reported_total_realization'), $request->validated('reported_ending_balance'), $request->validated('note'),
        );

        return redirect()->route('school.periodic.index', ['tahun' => $fiscalYear->year])->with('success', 'Laporan periodik tersimpan.');
    }
}
