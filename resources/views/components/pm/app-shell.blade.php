@props(['groups' => [], 'user'])
<div x-data="{ drawer: false }" class="min-h-screen">
    <div
        class="fixed inset-y-0 left-0 z-30"
        :class="drawer ? 'flex' : 'hidden nav:flex'"
    >
        <x-pm.sidebar :groups="$groups" class="flex" />
    </div>
    <div x-show="drawer" x-cloak class="fixed inset-0 z-20 bg-overlay nav:hidden" @click="drawer = false" aria-hidden="true"></div>

    <div class="flex min-h-screen flex-col nav:pl-sidebar">
        <x-pm.topbar :user="$user" />

        <main class="mx-auto flex w-full max-w-content flex-1 flex-col gap-6 p-4 nav:p-6">
            {{ $slot }}
        </main>

        <footer class="px-4 py-4 text-small text-ink-muted nav:px-6">
            SIMANABOS · Dinas Pendidikan dan Kebudayaan Kab. Hulu Sungai Selatan
        </footer>
    </div>
</div>
