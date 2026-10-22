{{-- Grafik batang SVG server-side. `series`: [['name', 'values' => [angka string...], 'display' => [teks...]]]; warna dari token chart-1..4. --}}
@props(['caption', 'labels' => [], 'series' => []])
@php
    $colors = ['var(--color-chart-1)', 'var(--color-chart-2)', 'var(--color-chart-3)', 'var(--color-chart-4)'];
    // Float hanya untuk geometri gambar, bukan perhitungan uang.
    $max = max(1.0, ...array_map(fn ($s) => max(array_map('floatval', $s['values'] ?: [0])), $series ?: [['values' => [0]]]));
    $count = max(1, count($labels));
    $groupW = 100 / $count;
    $barW = $groupW * 0.8 / max(1, count($series));
@endphp
<figure>
    <div class="mb-2 flex flex-wrap gap-4 text-small text-ink-muted" aria-hidden="true">
        @foreach ($series as $i => $s)
            <span class="inline-flex items-center gap-2"><span class="size-3 rounded-sm" style="background: {{ $colors[$i % 4] }}"></span>{{ $s['name'] }}</span>
        @endforeach
    </div>
    <svg viewBox="0 0 100 40" preserveAspectRatio="none" class="h-48 w-full" role="img" aria-label="{{ $caption }}">
        <line x1="0" y1="40" x2="100" y2="40" stroke="var(--color-line-strong)" stroke-width="0.3" vector-effect="non-scaling-stroke" />
        @foreach ($labels as $g => $label)
            @foreach ($series as $i => $s)
                @php $h = max(0, 38 * (float) ($s['values'][$g] ?? 0) / $max); @endphp
                <rect x="{{ $g * $groupW + $groupW * 0.1 + $i * $barW }}" y="{{ 40 - $h }}" width="{{ $barW }}" height="{{ $h }}" fill="{{ $colors[$i % 4] }}" />
            @endforeach
        @endforeach
    </svg>
    <div class="flex text-caption text-ink-muted" aria-hidden="true">
        @foreach ($labels as $label)<span class="flex-1 text-center">{{ $label }}</span>@endforeach
    </div>
    <details class="mt-3 text-small">
        <summary class="cursor-pointer font-semibold text-primary">Lihat sebagai tabel</summary>
        <x-pm.data-table :caption="$caption">
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-3 py-2">Bulan</th>
                    @foreach ($series as $s)<th scope="col" class="px-3 py-2 text-right">{{ $s['name'] }}</th>@endforeach
                </tr>
            </x-slot:head>
            @foreach ($labels as $g => $label)
                <tr>
                    <th scope="row" class="px-3 py-2 font-medium">{{ $label }}</th>
                    @foreach ($series as $s)<td class="px-3 py-2 text-right font-mono">{{ $s['display'][$g] ?? '' }}</td>@endforeach
                </tr>
            @endforeach
        </x-pm.data-table>
    </details>
</figure>
