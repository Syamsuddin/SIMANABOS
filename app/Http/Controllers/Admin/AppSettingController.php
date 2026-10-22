<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppSettingRequest;
use App\Models\AppSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AppSettingController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('viewAny', AppSetting::class);

        return view('admin.settings.edit', ['requirePrincipalApproval' => AppSetting::requiresPrincipalApproval()]);
    }

    public function update(AppSettingRequest $request): RedirectResponse
    {
        $old = AppSetting::requiresPrincipalApproval();
        $new = $request->boolean('require_principal_approval');
        AppSetting::put('require_principal_approval', $new);

        if ($old !== $new) {
            activity()->causedBy($request->user())
                ->withProperties(['key' => 'require_principal_approval', 'old' => $old, 'attributes' => $new])
                ->log('Pengaturan diubah');
        }

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan tersimpan.');
    }
}
