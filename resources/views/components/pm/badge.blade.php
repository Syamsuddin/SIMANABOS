@props(['tone' => 'neutral', 'icon' => null])
@php
    $tones = [
        'neutral' => 'bg-surface-sunken text-ink-muted',
        'info' => 'bg-info-soft text-info',
        'primary' => 'bg-primary-soft text-primary',
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-danger-soft text-danger',
        'accent' => 'bg-accent-soft text-accent',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-sm px-2 py-0.5 text-caption', $tones[$tone]]) }}>
    @if ($icon)<x-pm.icon :name="$icon" :size="16" />@endif
    {{ $slot }}
</span>
