@props(['tone' => 'success'])
@php
    $tones = [
        'info' => ['box' => 'border-info bg-info-soft', 'accent' => 'text-info', 'icon' => 'info'],
        'success' => ['box' => 'border-success bg-success-soft', 'accent' => 'text-success', 'icon' => 'check-circle'],
        'warning' => ['box' => 'border-warning bg-warning-soft', 'accent' => 'text-warning', 'icon' => 'alert'],
        'danger' => ['box' => 'border-danger bg-danger-soft', 'accent' => 'text-danger', 'icon' => 'x-circle'],
    ];
    $t = $tones[$tone];
@endphp
<div class="pointer-events-none fixed right-4 top-4 z-60 w-full max-w-sm">
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 5000)"
        x-show="show"
        x-cloak
        role="status"
        aria-live="polite"
        {{ $attributes->class(['pointer-events-auto flex items-start gap-3 rounded-md border px-4 py-3 text-body text-ink shadow-md', $t['box']]) }}
    >
        <x-pm.icon :name="$t['icon']" :size="20" class="mt-0.5 {{ $t['accent'] }}" />
        <div class="min-w-0 flex-1">{{ $slot }}</div>
        <button type="button" class="cursor-pointer rounded-sm text-ink-muted" aria-label="Tutup pemberitahuan" @click="show = false">
            <x-pm.icon name="x" :size="16" />
        </button>
    </div>
</div>
