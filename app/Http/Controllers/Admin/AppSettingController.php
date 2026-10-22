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

        return view('admin.settings.edit', [
            'requirePrincipalApproval' => AppSetting::requiresPrincipalApproval(),
            'defaultDeadlineDay' => AppSetting::defaultDeadlineDay(),
            'reminderDaysBefore' => AppSetting::reminderDaysBefore(),
        ]);
    }

    public function update(AppSettingRequest $request): RedirectResponse
    {
        $changes = [
            'require_principal_approval' => [AppSetting::requiresPrincipalApproval(), $request->boolean('require_principal_approval')],
            'default_deadline_day' => [AppSetting::defaultDeadlineDay(), (int) $request->validated('default_deadline_day')],
            'reminder_days_before' => [AppSetting::reminderDaysBefore(), (int) $request->validated('reminder_days_before')],
        ];

        foreach ($changes as $key => [$old, $new]) {
            AppSetting::put($key, $new);

            if ($old !== $new) {
                activity()->causedBy($request->user())
                    ->withProperties(['key' => $key, 'old' => $old, 'attributes' => $new])
                    ->log('Pengaturan diubah');
            }
        }

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan tersimpan.');
    }
}
