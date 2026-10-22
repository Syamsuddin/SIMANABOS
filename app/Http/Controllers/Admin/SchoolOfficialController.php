<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Schools\ApproveOfficial;
use App\Actions\Schools\ProposeOfficial;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\OfficialRequest;
use App\Models\School;
use App\Models\SchoolOfficial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SchoolOfficialController extends Controller
{
    public function store(OfficialRequest $request, School $school, ProposeOfficial $propose): RedirectResponse
    {
        $official = $propose($school, $request->validated(), $request->user());

        return redirect()->route('admin.schools.show', $school)
            ->with('success', $official->isPending() ? 'Usulan pengelola tercatat.' : 'Pengelola disahkan.');
    }

    public function approve(SchoolOfficial $official, ApproveOfficial $approve): RedirectResponse
    {
        Gate::authorize('approve', $official);

        $approve($official, request()->user());

        return redirect()->route('admin.schools.show', $official->school_id)->with('success', 'Pengelola disahkan.');
    }

    public function reject(SchoolOfficial $official): RedirectResponse
    {
        Gate::authorize('reject', $official);

        $official->delete();

        return redirect()->route('admin.schools.show', $official->school_id)->with('success', 'Usulan ditolak.');
    }
}
