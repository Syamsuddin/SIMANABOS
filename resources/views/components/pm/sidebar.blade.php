@props(['groups' => []])
<aside
    aria-label="Navigasi utama"
    {{ $attributes->class(['h-full w-sidebar flex-col bg-sidebar-bg text-sidebar-ink']) }}
>
    <div class="flex h-topbar flex-none items-center gap-3 border-b border-sidebar-active px-4">
        <img src="{{ asset('img/logo-hss.png') }}" alt="Lambang Kabupaten Hulu Sungai Selatan" class="size-control flex-none object-contain" />
        <div class="grid min-w-0 leading-snug">
            <strong class="truncate text-body font-bold">SIMANABOS</strong>
            <span class="truncate text-caption font-normal text-sidebar-ink-muted">Disdikbud Kab. Hulu Sungai Selatan</span>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 pb-4 pt-3">
        @foreach ($groups as $group)
            <div @class(['mt-4' => ! $loop->first])>
                <p class="px-3 pb-2 text-overline uppercase text-sidebar-ink-muted">{{ $group['title'] }}</p>
                <ul class="grid gap-0.5">
                    @foreach ($group['items'] as $item)
                        @php $active = request()->routeIs($item['route']); @endphp
                        <li>
                            <a
                                href="{{ route(str_replace('.*', '.index', $item['route'])) }}"
                                @if ($active) aria-current="page" @endif
                                @class([
                                    'relative flex min-h-10 items-center gap-3 rounded-md px-3 text-body transition-colors duration-150 hover:bg-sidebar-active',
                                    'bg-sidebar-active font-semibold' => $active,
                                    'font-medium' => ! $active,
                                ])
                            >
                                <x-pm.icon :name="$item['icon']" :size="20" :class="$active ? 'text-sidebar-ink' : 'text-sidebar-ink-muted'" />
                                <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
</aside>
