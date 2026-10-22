@props(['href', 'variant' => 'primary', 'size' => 'md', 'icon' => null])
@php
    $variants = [
        'primary' => 'bg-primary text-on-primary hover:bg-primary-hover',
        'secondary' => 'bg-surface-raised text-ink border-line-strong hover:bg-surface-sunken',
        'ghost' => 'bg-transparent text-primary hover:bg-primary-soft',
    ];
    $sizes = ['sm' => 'h-8 px-3 text-small font-semibold', 'md' => 'h-control px-4 text-body font-semibold'];
@endphp
<a href="{{ $href }}" {{ $attributes->class([
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md border border-transparent transition-colors duration-150',
    $variants[$variant], $sizes[$size],
]) }}>
    @if ($icon)<x-pm.icon :name="$icon" />@endif
    {{ $slot }}
</a>
