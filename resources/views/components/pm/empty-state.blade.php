@props(['icon' => 'folder', 'title', 'description' => null])
<div {{ $attributes->class(['grid justify-items-center gap-2 px-5 py-12 text-center']) }}>
    <x-pm.icon :name="$icon" :size="20" class="text-ink-muted" />
    <p class="text-h3 text-ink">{{ $title }}</p>
    @if ($description)<p class="max-w-md text-body text-ink-muted">{{ $description }}</p>@endif
    @isset($actions)<div class="mt-2">{{ $actions }}</div>@endisset
</div>
