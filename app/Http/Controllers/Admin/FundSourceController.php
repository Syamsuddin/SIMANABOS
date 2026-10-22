<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FundSourceRequest;
use App\Models\FundSource;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class FundSourceController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', FundSource::class);

        return view('admin.fund-sources.index', ['fundSources' => FundSource::query()->orderBy('code')->get()]);
    }

    public function create(): View
    {
        Gate::authorize('create', FundSource::class);

        return view('admin.fund-sources.form', ['fundSource' => new FundSource(['is_active' => true])]);
    }

    public function store(FundSourceRequest $request): RedirectResponse
    {
        FundSource::query()->create($request->validated());

        return redirect()->route('admin.fund-sources.index')->with('success', 'Sumber dana tersimpan.');
    }

    public function edit(FundSource $fundSource): View
    {
        Gate::authorize('update', $fundSource);

        return view('admin.fund-sources.form', ['fundSource' => $fundSource]);
    }

    public function update(FundSourceRequest $request, FundSource $fundSource): RedirectResponse
    {
        $fundSource->update($request->validated());

        return redirect()->route('admin.fund-sources.index')->with('success', 'Sumber dana tersimpan.');
    }
}
