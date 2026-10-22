@props([
    'label',
    'name',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'optional' => false,
    'hint' => null,
    'error' => null,
    'icon' => null,
    'mono' => false,
    'multiline' => false,
])
@php
    $id ??= $name;
    // Nama array (`a[1]`) dipetakan ke kunci titik (`a.1`) untuk old() dan error bag.
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $error ??= $errors->first($key);
    $describedBy = trim(($error ? "{$id}-error " : '').($hint && ! $error ? "{$id}-hint" : ''));
    // Sandi tidak pernah diisi ulang dari input lama.
    $current = $type === 'password' ? null : old($key, $value);
@endphp
<div class="grid min-w-0 gap-2">
    <label for="{{ $id }}" class="text-small font-semibold text-ink">
        {{ $label }}
        @if ($required)
            <span class="text-danger" aria-hidden="true">*</span>
        @elseif ($optional)
            <span class="font-normal text-ink-muted">(opsional)</span>
        @endif
    </label>

    <div class="relative flex items-center">
        @if ($icon)
            <x-pm.icon :name="$icon" class="pointer-events-none absolute left-3 text-ink-muted" />
        @endif
        @if ($multiline)
        <textarea
            id="{{ $id }}"
            name="{{ $name }}"
            rows="3"
            @if ($required) aria-required="true" @endif
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class([
                'w-full rounded-md border bg-surface-raised px-3 py-2 text-body text-ink placeholder:text-ink-subtle',
                'hover:enabled:border-ink-muted focus:border-primary focus:outline-2 focus:outline-offset-1 focus:outline-focus',
                'disabled:cursor-not-allowed disabled:bg-surface-sunken disabled:text-ink-muted',
                'border-line-strong' => ! $error,
                'border-danger' => $error,
            ]) }}
        >{{ $current }}</textarea>
        @else
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @if ($current !== null) value="{{ $current }}" @endif
            @if ($required) aria-required="true" @endif
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class([
                'h-control w-full rounded-md border bg-surface-raised px-3 text-body text-ink placeholder:text-ink-subtle',
                'hover:enabled:border-ink-muted focus:border-primary focus:outline-2 focus:outline-offset-1 focus:outline-focus',
                'disabled:cursor-not-allowed disabled:bg-surface-sunken disabled:text-ink-muted',
                'border-line-strong' => ! $error,
                'border-danger' => $error,
                'pl-control' => $icon,
                'font-mono text-mono' => $mono,
            ]) }}
        />
        @endif
    </div>

    @if ($error)
        <p id="{{ $id }}-error" class="flex items-center gap-1 text-small font-medium text-danger">
            <x-pm.icon name="alert" :size="16" />
            {{ $error }}
        </p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="text-small text-ink-muted">{{ $hint }}</p>
    @endif
</div>
