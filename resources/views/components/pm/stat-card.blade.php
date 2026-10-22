@props(['label', 'value', 'unit' => null, 'icon' => null, 'caption' => null])
<div {{ $attributes->class(['rounded-lg border border-line bg-surface-raised p-5 shadow-sm']) }}>
    <div class="flex items-center justify-between gap-2">
        <p class="text-caption text-ink-muted">{{ $label }}</p>
        @if ($icon)<x-pm.icon :name="$icon" :size="20" class="text-ink-muted" />@endif
    </div>
    <p class="mt-2 font-mono text-stat text-ink">@if ($unit)<span class="text-small font-medium text-ink-muted">{{ $unit }}</span> @endif{{ $value }}</p>
    @if ($caption)<p class="mt-1 text-small text-ink-muted">{{ $caption }}</p>@endif
</div>
