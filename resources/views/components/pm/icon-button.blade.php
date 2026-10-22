@props([
    'label',
    'icon',
    'variant' => 'ghost',
    'type' => 'button',
])
@php
    $variants = [
        'ghost' => 'border-transparent bg-transparent text-ink-muted hover:enabled:bg-surface-sunken',
        'outline' => 'border-line-strong bg-surface-raised text-ink hover:enabled:bg-surface-sunken',
    ];
@endphp
<button
    type="{{ $type }}"
    aria-label="{{ $label }}"
    title="{{ $label }}"
    {{ $attributes->class([
        'inline-flex size-control cursor-pointer items-center justify-center rounded-md border transition-colors duration-150',
        'disabled:cursor-not-allowed disabled:opacity-50',
        $variants[$variant],
    ]) }}
>
    <x-pm.icon :name="$icon" :size="20" />
</button>
