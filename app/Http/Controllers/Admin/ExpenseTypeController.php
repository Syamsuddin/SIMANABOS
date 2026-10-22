<?php

namespace App\Http\Controllers\Admin;

use App\Actions\FiscalYears\CopyExpenseTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExpenseTypeRequest;
use App\Models\ExpenseType;
use App\Models\FiscalYear;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ExpenseTypeController extends Controller
{
    public function index(FiscalYear $fiscalYear): View
    {
        Gate::authorize('viewAny', ExpenseType::class);

        return view('admin.expense-types.index', [
            'fiscalYear' => $fiscalYear,
            'expenseTypes' => $fiscalYear->expenseTypes()->get(),
            'canEdit' => request()->user()->can('create', [ExpenseType::class, $fiscalYear]),
        ]);
    }

    public function create(FiscalYear $fiscalYear): View
    {
        Gate::authorize('create', [ExpenseType::class, $fiscalYear]);

        return view('admin.expense-types.form', [
            'fiscalYear' => $fiscalYear,
            'expenseType' => new ExpenseType(['is_active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(ExpenseTypeRequest $request, FiscalYear $fiscalYear): RedirectResponse
    {
        $fiscalYear->expenseTypes()->create($request->validated());

        return redirect()->route('admin.fiscal-years.expense-types.index', $fiscalYear)->with('success', 'Jenis belanja tersimpan.');
    }

    public function edit(FiscalYear $fiscalYear, ExpenseType $expenseType): View
    {
        Gate::authorize('update', $expenseType);

        return view('admin.expense-types.form', ['fiscalYear' => $fiscalYear, 'expenseType' => $expenseType]);
    }

    public function update(ExpenseTypeRequest $request, FiscalYear $fiscalYear, ExpenseType $expenseType): RedirectResponse
    {
        $expenseType->update($request->validated());

        return redirect()->route('admin.fiscal-years.expense-types.index', $fiscalYear)->with('success', 'Jenis belanja tersimpan.');
    }

    public function copy(FiscalYear $fiscalYear, CopyExpenseTypes $copyExpenseTypes): RedirectResponse
    {
        Gate::authorize('create', [ExpenseType::class, $fiscalYear]);

        $copied = $copyExpenseTypes->handle($fiscalYear);

        return redirect()->route('admin.fiscal-years.expense-types.index', $fiscalYear)->with(
            'success',
            $copied > 0 ? "{$copied} jenis belanja disalin dari tahun sebelumnya." : 'Tidak ada jenis belanja baru untuk disalin.'
        );
    }
}
