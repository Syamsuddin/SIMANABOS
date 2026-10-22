<?php

namespace App\Http\Controllers\School;

use App\Actions\Budget\SaveOpeningBalance;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\OpeningBalanceRequest;
use App\Models\Allocation;
use App\Models\FiscalYear;
use App\Models\FundSource;
use App\Models\OpeningBalance;
use App\Models\School;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OpeningBalanceController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', OpeningBalance::class);
        abort_if($request->user()->school_id === null, 404);

        $fiscalYear = FiscalYear::resolve($request->integer('tahun') ?: null);
        $rows = collect();
        if ($fiscalYear) {
            $balances = OpeningBalance::query()->where('fiscal_year_id', $fiscalYear->id)->get()->keyBy('fund_source_id');
            $rows = Allocation::query()->where('fiscal_year_id', $fiscalYear->id)->with('fundSource')->get()
                ->map(fn (Allocation $a) => ['fundSource' => $a->fundSource, 'balance' => $balances->get($a->fund_source_id)]);
        }

        return view('school.opening-balance.index', [
            'fiscalYear' => $fiscalYear,
            'fiscalYears' => FiscalYear::query()->orderByDesc('year')->get(),
            'rows' => $rows,
            'canEdit' => $fiscalYear !== null && $request->user()->can('save', [OpeningBalance::class, $fiscalYear]),
        ]);
    }

    public function update(OpeningBalanceRequest $request, SaveOpeningBalance $saveOpeningBalance): RedirectResponse
    {
        $school = School::query()->findOrFail($request->user()->school_id);
        $fiscalYear = FiscalYear::query()->findOrFail($request->integer('fiscal_year_id'));
        $fundSource = FundSource::query()->findOrFail($request->integer('fund_source_id'));

        $saveOpeningBalance($school, $fiscalYear, $fundSource, $request->validated('fund_amount'), $request->validated('tax_amount'));

        return redirect()->route('school.opening-balance.index', ['tahun' => $fiscalYear->year])
            ->with('success', 'Saldo awal tersimpan dan menunggu verifikasi pemeriksa.');
    }
}
