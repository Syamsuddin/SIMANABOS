<?php

namespace App\Http\Controllers\School;

use App\Actions\Schools\ProposeOfficial;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\OfficialRequest;
use App\Http\Requests\School\ProfileRequest;
use App\Models\School;
use App\Models\SchoolOfficial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $school = $this->school($request);
        Gate::authorize('view', $school);

        return view('school.profile.show', [
            'school' => $school->load('district'),
            'officials' => SchoolOfficial::query()->where('school_id', $school->id)->orderByDesc('started_at')->get(),
        ]);
    }

    public function edit(Request $request): View
    {
        $school = $this->school($request);
        Gate::authorize('updateProfile', $school);

        return view('school.profile.edit', ['school' => $school]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $this->school($request)->update($request->validated());

        return redirect()->route('school.profile.show')->with('success', 'Profil sekolah tersimpan.');
    }

    public function storeOfficial(OfficialRequest $request, ProposeOfficial $propose): RedirectResponse
    {
        $propose($this->school($request), $request->validated(), $request->user());

        return redirect()->route('school.profile.show')->with('success', 'Usulan pengelola tercatat dan menunggu pengesahan Dinas.');
    }

    /** Akun tanpa `school_id` (peran dinas) tidak punya profil sekolah: 404. */
    private function school(Request $request): School
    {
        abort_if($request->user()->school_id === null, 404);

        return School::query()->findOrFail($request->user()->school_id);
    }
}
