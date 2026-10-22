<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SchoolLevel;
use App\Enums\SchoolStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SchoolRequest;
use App\Models\District;
use App\Models\School;
use App\Models\SchoolOfficial;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SchoolController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', School::class);

        $schools = School::query()
            ->with('district')
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(
                fn ($w) => $w->where('name', 'like', '%'.addcslashes($term, '%_\\').'%')->orWhere('npsn', 'like', addcslashes($term, '%_\\').'%')
            ))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.schools.index', ['schools' => $schools, 'term' => $request->string('q')->trim()->value()]);
    }

    public function create(): View
    {
        Gate::authorize('create', School::class);

        return view('admin.schools.form', $this->formData(new School));
    }

    public function store(SchoolRequest $request): RedirectResponse
    {
        $school = School::query()->create($request->validated());

        return redirect()->route('admin.schools.show', $school)->with('success', 'Sekolah tersimpan.');
    }

    public function show(School $school): View
    {
        Gate::authorize('update', $school);

        return view('admin.schools.show', [
            'school' => $school->load('district'),
            'officials' => SchoolOfficial::query()->where('school_id', $school->id)->orderByDesc('started_at')->get(),
            'accounts' => User::query()->where('school_id', $school->id)->orderBy('name')->get(),
        ]);
    }

    public function edit(School $school): View
    {
        Gate::authorize('update', $school);

        return view('admin.schools.form', $this->formData($school));
    }

    public function update(SchoolRequest $request, School $school): RedirectResponse
    {
        $school->update($request->validated());

        return redirect()->route('admin.schools.show', $school)->with('success', 'Sekolah tersimpan.');
    }

    /** Sekolah tidak pernah dihapus; hanya dinonaktifkan atau diaktifkan kembali. */
    public function toggleActive(School $school): RedirectResponse
    {
        Gate::authorize('update', $school);

        $school->update(['is_active' => ! $school->is_active]);

        return redirect()->route('admin.schools.show', $school)
            ->with('success', $school->is_active ? 'Sekolah diaktifkan.' : 'Sekolah dinonaktifkan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(School $school): array
    {
        return [
            'school' => $school,
            'districts' => District::query()->orderBy('name')->get(),
            'levels' => SchoolLevel::cases(),
            'statuses' => SchoolStatus::cases(),
        ];
    }
}
