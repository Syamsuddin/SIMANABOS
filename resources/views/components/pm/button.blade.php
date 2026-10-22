@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'icon' => null,
    'iconRight' => null,
    'loading' => false,
    'block' => false,
])
@php
    $variants = [
        'primary' => 'bg-primary text-on-primary hover:enabled:bg-primary-hover',
        'secondary' => 'bg-surface-raised text-ink border-line-strong hover:enabled:bg-surface-sunken',
        'ghost' => 'bg-transparent text-primary hover:enabled:bg-primary-soft',
        'danger' => 'bg-danger text-on-danger hover:enabled:brightness-90',
        'accent' => 'bg-accent text-on-accent hover:enabled:brightness-90',
    ];
    $sizes = [
        'sm' => 'h-8 px-3 text-small font-semibold',
        'md' => 'h-control px-4 text-body font-semibold',
        'lg' => 'h-11 px-5 text-h3 font-semibold',
    ];
@endphp
<button
    type="{{ $type }}"
    @disabled($loading)
    @if ($loading) aria-busy="true" @endif
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md border border-transparent transition-colors duration-150',
        'disabled:cursor-not-allowed disabled:opacity-50',
        'cursor-pointer' => ! $loading,
        $variants[$variant],
        $sizes[$size],
        'w-full' => $block,
        'cursor-progress opacity-80' => $loading,
    ]) }}
>
    @if ($icon)<x-pm.icon :name="$icon" />@endif
    {{ $slot }}
    @if ($iconRight)<x-pm.icon :name="$iconRight" />@endif
</button>
