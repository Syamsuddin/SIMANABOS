@props(['label', 'name', 'checked' => false, 'hint' => null])
@php $isChecked = (bool) old($name, $checked); @endphp
<div class="grid gap-1">
    <label class="flex min-h-control items-center gap-3 text-body text-ink">
        <input type="hidden" name="{{ $name }}" value="0">
        <input
            type="checkbox"
            id="{{ $name }}"
            name="{{ $name }}"
            value="1"
            @checked($isChecked)
            {{ $attributes->class(['size-5 rounded-sm border-line-strong accent-primary focus:outline-2 focus:outline-offset-2 focus:outline-focus']) }}
        />
        {{ $label }}
    </label>
    @if ($hint)<p class="pl-8 text-small text-ink-muted">{{ $hint }}</p>@endif
</div>
