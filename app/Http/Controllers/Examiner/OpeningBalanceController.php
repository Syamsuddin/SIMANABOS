<?php

namespace App\Http\Controllers\Examiner;

use App\Actions\Budget\VerifyOpeningBalance;
use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use App\Models\OpeningBalance;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OpeningBalanceController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', OpeningBalance::class);
        abort_if($request->user()->isSchoolUser(), 404);

        $fiscalYear = FiscalYear::resolve($request->integer('tahun') ?: null);
        $onlyPending = $request->query('status', 'menunggu') === 'menunggu';

        $balances = $fiscalYear
            ? OpeningBalance::query()->where('fiscal_year_id', $fiscalYear->id)
                ->when($onlyPending, fn ($q) => $q->whereNull('verified_at'))
                ->with(['school', 'fundSource', 'verifier'])->orderBy('school_id')->get()
            : collect();

        return view('examiner.opening-balances.index', [
            'fiscalYear' => $fiscalYear,
            'fiscalYears' => FiscalYear::query()->orderByDesc('year')->get(),
            'balances' => $balances,
            'onlyPending' => $onlyPending,
        ]);
    }

    public function verify(Request $request, OpeningBalance $openingBalance, VerifyOpeningBalance $verify): RedirectResponse
    {
        Gate::authorize('verify', $openingBalance);

        $verify($openingBalance, $request->user());

        return back()->with('success', 'Saldo awal diverifikasi.');
    }
}
