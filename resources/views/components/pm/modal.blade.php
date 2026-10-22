@props(['id', 'title', 'action', 'method' => null, 'tone' => 'info', 'trigger' => 'Buka', 'triggerVariant' => 'secondary', 'triggerSize' => 'md', 'confirm' => 'Lanjutkan'])
{{-- Konfirmasi aksi destruktif/irreversibel (docs/26). Isi slot = penjelasan; form berada di dalam modal. --}}
<div x-data="{ open: false }" @keydown.escape.window="open = false">
    <x-pm.button type="button" :variant="$triggerVariant" :size="$triggerSize" x-on:click="open = true" aria-haspopup="dialog">{{ $trigger }}</x-pm.button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-overlay p-4">
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="{{ $id }}-title"
            @click.outside="open = false"
            class="w-full max-w-md rounded-lg bg-surface-raised p-5 shadow-lg"
        >
            <h2 id="{{ $id }}-title" class="text-h2 text-ink">{{ $title }}</h2>
            <div class="mt-3 text-body text-ink-muted">{{ $slot }}</div>
            <form method="POST" action="{{ $action }}" class="mt-5" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                @if ($method) @method($method) @endif
                @isset($fields)<div class="mb-4">{{ $fields }}</div>@endisset
                <div class="flex justify-end gap-2">
                    <x-pm.button type="button" variant="secondary" x-on:click="open = false">Batal</x-pm.button>
                    <x-pm.button type="submit" :variant="$tone === 'danger' ? 'danger' : 'primary'" x-bind:disabled="busy" x-bind:aria-busy="busy">{{ $confirm }}</x-pm.button>
                </div>
            </form>
        </div>
    </div>
</div>
