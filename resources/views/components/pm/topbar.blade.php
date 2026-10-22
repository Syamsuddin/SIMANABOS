@props(['user'])
<header class="sticky top-0 z-20 flex h-topbar items-center gap-3 border-b border-line bg-surface-raised px-4 nav:px-5">
    <x-pm.icon-button label="Buka menu" icon="menu" class="nav:hidden" @click="drawer = ! drawer" />

    <div class="ml-auto flex items-center gap-2">
        <div class="hidden text-right leading-snug nav:grid">
            <strong class="text-small font-semibold text-ink">{{ $user->name }}</strong>
            <span class="text-caption font-normal text-ink-muted">{{ $user->getRoleNames()->map(fn ($role) => \App\Enums\RoleName::tryFrom($role)?->label() ?? $role)->implode(', ') }}</span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-pm.button type="submit" variant="secondary" size="sm" icon="logout">Keluar</x-pm.button>
        </form>
    </div>
</header>
