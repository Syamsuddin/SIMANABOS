<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DistrictRequest;
use App\Models\District;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DistrictController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', District::class);

        return view('admin.districts.index', ['districts' => District::query()->orderBy('name')->get()]);
    }

    public function create(): View
    {
        Gate::authorize('create', District::class);

        return view('admin.districts.form', ['district' => new District]);
    }

    public function store(DistrictRequest $request): RedirectResponse
    {
        District::query()->create($request->validated());

        return redirect()->route('admin.districts.index')->with('success', 'Kecamatan tersimpan.');
    }

    public function edit(District $district): View
    {
        Gate::authorize('update', $district);

        return view('admin.districts.form', ['district' => $district]);
    }

    public function update(DistrictRequest $request, District $district): RedirectResponse
    {
        $district->update($request->validated());

        return redirect()->route('admin.districts.index')->with('success', 'Kecamatan tersimpan.');
    }
}
