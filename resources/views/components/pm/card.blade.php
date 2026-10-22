@props(['title' => null, 'subtitle' => null, 'flush' => false])
<section {{ $attributes->class(['rounded-lg border border-line bg-surface-raised shadow-sm']) }}>
    @if ($title || isset($actions))
        <header class="flex items-start justify-between gap-4 px-5 pt-5">
            <div class="min-w-0">
                @if ($title)<h2 class="text-h2 text-ink">{{ $title }}</h2>@endif
                @if ($subtitle)<p class="text-small text-ink-muted">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div @class(['p-5' => ! $flush, 'pt-4' => ! $flush && ($title || isset($actions))])>{{ $slot }}</div>
    @isset($footer)
        <footer class="border-t border-line px-5 py-3">{{ $footer }}</footer>
    @endisset
</section>
