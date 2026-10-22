@props(['title', 'description' => null, 'breadcrumb' => []])
<div class="flex flex-wrap items-start justify-between gap-4">
    <div class="min-w-0">
        @if (count($breadcrumb) > 0)
            <nav aria-label="Jejak halaman" class="mb-1">
                <ol class="flex flex-wrap items-center gap-1 text-small text-ink-muted">
                    @foreach ($breadcrumb as $crumb)
                        <li class="flex items-center gap-1">
                            @if (! $loop->first)<x-pm.icon name="chevron-right" :size="16" />@endif
                            {{ $crumb }}
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
        <h1 class="text-h1 text-ink">{{ $title }}</h1>
        @if ($description)<p class="mt-1 text-body text-ink-muted">{{ $description }}</p>@endif
    </div>
    @isset($actions)<div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>@endisset
</div>
