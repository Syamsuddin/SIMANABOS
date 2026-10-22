@extends('layouts.app')

@section('title', 'Dasbor Kabupaten')

@php
    $monthName = fn ($m) => \Illuminate\Support\Carbon::create(null, $m, 1)->locale('id')->translatedFormat('F');
    $monthShort = fn ($m) => \Illuminate\Support\Carbon::create(null, $m, 1)->locale('id')->translatedFormat('M');
    $months = collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => $monthName($m)])->all();
    $rp = fn ($v) => \App\Support\Money::format($v);
@endphp

@section('content')
    <x-pm.page-header title="Dasbor Kabupaten" description="Posisi dana BOS seluruh sekolah dari laporan yang sudah diajukan. ARKAS tetap sistem resmi." :breadcrumb="['Utama', 'Dasbor']" />

    <x-pm.card class="mt-5">
        <form method="GET" action="{{ route('dashboard.index') }}" class="grid items-end gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-pm.select name="tahun" label="Tahun anggaran" :options="$fiscalYears->pluck('year', 'year')->all()" :value="$fiscalYear?->year" placeholder="Pilih tahun" />
            <x-pm.select name="dari" label="Dari bulan" :options="$months" :value="$from" placeholder="Januari" />
            <x-pm.select name="sampai" label="Sampai bulan" :options="$months" :value="$to" placeholder="Desember" />
            <x-pm.select name="jenjang" label="Jenjang" :options="['SD' => 'SD', 'SMP' => 'SMP']" :value="request('jenjang')" placeholder="Semua jenjang" />
            <x-pm.select name="kecamatan" label="Kecamatan" :options="$districts" :value="request('kecamatan')" placeholder="Semua kecamatan" />
            <x-pm.select name="sumber_dana" label="Sumber dana" :options="$fundSources" :value="request('sumber_dana')" placeholder="Semua sumber dana" />
            <x-pm.select name="status" label="Status laporan" :options="collect(\App\Enums\ReportStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" :value="request('status')" placeholder="Semua status" />
            <x-pm.button type="submit" variant="secondary">Terapkan</x-pm.button>
        </form>
    </x-pm.card>

    @if ($summary === null)
        <x-pm.card class="mt-5"><x-pm.empty-state icon="calendar" title="Belum ada tahun anggaran" description="Buka tahun anggaran terlebih dahulu di menu Pengaturan." /></x-pm.card>
    @elseif ($summary['total'] === 0)
        <x-pm.card class="mt-5"><x-pm.empty-state icon="chart" title="Tidak ada laporan pada filter ini" description="Ubah filter atau tunggu sekolah membuat laporan." /></x-pm.card>
    @else
        <p class="mt-5 text-small text-ink-muted">Angka dihitung dari <strong>{{ $summary['counted'] }} dari {{ $summary['total'] }} laporan</strong> (hanya yang sudah diajukan) · {{ $monthName($from) }}–{{ $monthName($to) }} {{ $fiscalYear->year }}.</p>

        <div class="mt-3 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <x-pm.stat-card label="Penerimaan" icon="wallet" unit="Rp" :value="$rp($summary['receipt'])" caption="Dana BOS + penerimaan lain" />
            <x-pm.stat-card label="Realisasi neto" icon="trend-up" unit="Rp" :value="$rp($summary['realization'])" caption="Belanja dikurangi pengembalian" />
            <x-pm.stat-card label="Saldo dana akhir {{ $monthName($to) }}" icon="wallet" unit="Rp" :value="$rp($summary['ending_fund'])" caption="Posisi bulan terakhir, bukan jumlah antarbulan" />
            <x-pm.stat-card label="Saldo pajak akhir {{ $monthName($to) }}" icon="wallet" unit="Rp" :value="$rp($summary['ending_tax'])" caption="Pajak belum disetor" />
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-[2fr_1fr]">
            <x-pm.card title="Penerimaan dan realisasi per bulan">
                <x-pm.bar-chart
                    caption="Penerimaan dan realisasi neto per bulan"
                    :labels="$summary['monthly']->map(fn ($m) => $monthShort($m['month']))->all()"
                    :series="[
                        ['name' => 'Penerimaan', 'values' => $summary['monthly']->pluck('receipt')->all(), 'display' => $summary['monthly']->map(fn ($m) => $rp($m['receipt']))->all()],
                        ['name' => 'Realisasi neto', 'values' => $summary['monthly']->pluck('realization')->all(), 'display' => $summary['monthly']->map(fn ($m) => $rp($m['realization']))->all()],
                    ]" />
            </x-pm.card>

            <x-pm.card title="Status laporan" subtitle="Semua laporan pada filter, termasuk draf.">
                <ul class="grid gap-3">
                    @foreach (\App\Enums\ReportStatus::cases() as $status)
                        <li class="flex items-center justify-between gap-3">
                            <x-pm.status-badge :status="$status" />
                            <span class="font-mono text-body">{{ $summary['by_status'][$status->value] ?? 0 }}</span>
                        </li>
                    @endforeach
                    <li class="flex items-center justify-between gap-3 border-t border-line pt-3">
                        <span class="text-body text-danger">Terlambat</span><span class="font-mono text-body">{{ $summary['late'] }}</span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-body text-accent">Perlu tinjau ulang</span><span class="font-mono text-body">{{ $summary['rereview'] }}</span>
                    </li>
                </ul>
            </x-pm.card>
        </div>

        <x-pm.card flush class="mt-5" title="Per kecamatan">
            <x-pm.data-table caption="Ringkasan per kecamatan">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Kecamatan</th>
                        <th scope="col" class="px-5 py-3 text-right">Laporan</th>
                        <th scope="col" class="px-5 py-3 text-right">Penerimaan</th>
                        <th scope="col" class="px-5 py-3 text-right">Realisasi neto</th>
                        <th scope="col" class="px-5 py-3 text-right">Saldo dana akhir</th>
                    </tr>
                </x-slot:head>
                @foreach ($summary['by_district'] as $row)
                    <tr>
                        <td class="px-5 py-3">{{ $row['name'] }}</td>
                        <td class="px-5 py-3 text-right font-mono">{{ $row['counted'] }} dari {{ $row['total'] }}</td>
                        <td class="px-5 py-3 text-right font-mono">{{ $rp($row['receipt']) }}</td>
                        <td class="px-5 py-3 text-right font-mono">{{ $rp($row['realization']) }}</td>
                        <td class="px-5 py-3 text-right font-mono">{{ $rp($row['ending_fund']) }}</td>
                    </tr>
                @endforeach
            </x-pm.data-table>
        </x-pm.card>
    @endif
@endsection
