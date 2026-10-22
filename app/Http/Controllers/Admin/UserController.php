<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::query()->with(['roles', 'school'])->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.form', $this->formData(new User));
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'username' => $data['username'],
            'name' => $data['name'],
            'password' => $data['password'],
            'school_id' => $data['school_id'] ?? null,
            'must_change_password' => true,
            'is_active' => true,
        ]);
        $user->syncRoles([$data['role']]);

        return redirect()->route('admin.users.index')->with('success', 'Akun tersimpan. Pengguna wajib mengganti sandi saat masuk pertama.');
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.form', $this->formData($user));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->update([
            'name' => $data['name'],
            'school_id' => $data['school_id'] ?? null,
            'is_active' => $data['is_active'],
        ]);
        $user->syncRoles([$data['role']]);

        return redirect()->route('admin.users.index')->with('success', 'Akun tersimpan.');
    }

    public function resetPassword(ResetPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'password' => $request->validated('password'),
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.users.edit', $user)
            ->with('success', 'Sandi sementara disimpan. Pengguna wajib menggantinya saat masuk.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(User $user): array
    {
        return [
            'account' => $user,
            'roles' => RoleName::cases(),
            'schools' => School::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
