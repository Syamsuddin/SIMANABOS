@props(['label', 'name', 'id' => null, 'options' => [], 'value' => null, 'required' => false, 'optional' => false, 'hint' => null, 'placeholder' => 'Pilih…'])
@php
    $id ??= $name;
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $error = $errors->first($key);
    $describedBy = trim(($error ? "{$id}-error " : '').($hint && ! $error ? "{$id}-hint" : ''));
    $current = (string) old($key, $value);
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

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($required) aria-required="true" @endif
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'h-control w-full rounded-md border bg-surface-raised px-3 text-body text-ink',
            'hover:enabled:border-ink-muted focus:border-primary focus:outline-2 focus:outline-offset-1 focus:outline-focus',
            'disabled:cursor-not-allowed disabled:bg-surface-sunken disabled:text-ink-muted',
            'border-line-strong' => ! $error,
            'border-danger' => $error,
        ]) }}
    >
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @if ($error)
        <p id="{{ $id }}-error" class="flex items-center gap-1 text-small font-medium text-danger">
            <x-pm.icon name="alert" :size="16" />
            {{ $error }}
        </p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="text-small text-ink-muted">{{ $hint }}</p>
    @endif
</div>
