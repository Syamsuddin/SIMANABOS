@extends('layouts.app')

@section('title', 'Rekap')

@php
    $rp = fn ($v) => \App\Support\Money::format($v);
    $query = array_filter(request()->only(['tahun', 'periode', 'jenjang', 'kecamatan', 'sumber_dana']));
@endphp

@section('content')
    <x-pm.page-header title="Rekap" description="Rekap dana BOS per sekolah untuk triwulan, semester, atau tahun. Saldo diambil dari posisi bulan terakhir periode." :breadcrumb="['Utama', 'Rekap']">
        <x-slot:actions>
            @if ($rows !== null && $rows->isNotEmpty())
                <x-pm.link-button :href="route('recap.export', $query)" variant="secondary">Unduh XLSX</x-pm.link-button>
            @endif
        </x-slot:actions>
    </x-pm.page-header>

    <x-pm.card class="mt-5">
        <form method="GET" action="{{ route('recap.index') }}" class="grid items-end gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-pm.select name="tahun" label="Tahun anggaran" :options="$fiscalYears->pluck('year', 'year')->all()" :value="$fiscalYear?->year" placeholder="Pilih tahun" />
            <x-pm.select name="periode" label="Periode" :options="$periods" :value="$period->value" />
            <x-pm.select name="jenjang" label="Jenjang" :options="['SD' => 'SD', 'SMP' => 'SMP']" :value="request('jenjang')" placeholder="Semua jenjang" />
            <x-pm.select name="kecamatan" label="Kecamatan" :options="$districts" :value="request('kecamatan')" placeholder="Semua kecamatan" />
            <x-pm.select name="sumber_dana" label="Sumber dana" :options="$fundSources" :value="request('sumber_dana')" placeholder="Semua sumber dana" />
            <x-pm.button type="submit" variant="secondary">Terapkan</x-pm.button>
        </form>
    </x-pm.card>

    @if ($rows === null)
        <x-pm.card class="mt-5"><x-pm.empty-state icon="calendar" title="Belum ada tahun anggaran" description="Buka tahun anggaran terlebih dahulu di menu Pengaturan." /></x-pm.card>
    @elseif ($rows->isEmpty())
        <x-pm.card class="mt-5"><x-pm.empty-state icon="chart" title="Tidak ada laporan terajukan pada periode ini" description="Rekap hanya menghitung laporan yang sudah diajukan. Ubah filter atau periode." /></x-pm.card>
    @else
        <p class="mt-5 text-small text-ink-muted">{{ $period->label() }} {{ $fiscalYear->year }} · {{ $rows->count() }} sekolah · {{ $totals['reports'] }} laporan terajukan.</p>

        <x-pm.card flush class="mt-3" title="Rekap per sekolah">
            <x-pm.data-table caption="Rekap per sekolah {{ $period->label() }} {{ $fiscalYear->year }}">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Sekolah</th>
                        <th scope="col" class="px-5 py-3">Kecamatan</th>
                        <th scope="col" class="px-5 py-3 text-right">Saldo awal</th>
                        <th scope="col" class="px-5 py-3 text-right">Penerimaan</th>
                        <th scope="col" class="px-5 py-3 text-right">Realisasi neto</th>
                        <th scope="col" class="px-5 py-3 text-right">Saldo dana akhir</th>
                        <th scope="col" class="px-5 py-3 text-right">Saldo pajak akhir</th>
                    </tr>
                </x-slot:head>
                @foreach ($rows as $row)
                    <tr>
                        <td class="px-5 py-3">{{ $row['school'] }} <span class="text-small text-ink-muted">{{ $row['level'] }} · {{ $row['npsn'] }}</span></td>
                        <td class="px-5 py-3">{{ $row['district'] }}</td>
                        <td class="px-5 py-3 text-right font-mono">{{ $rp($row['opening_fund']) }}</td>
                        <td class="px-5 py-3 text-right font-mono">{{ $rp($row['receipt']) }}</td>
                        <td class="px-5 py-3 text-right font-mono">{{ $rp($row['realization']) }}</td>
                        <td class="px-5 py-3 text-right font-mono">{{ $rp($row['ending_fund']) }}</td>
                        <td class="px-5 py-3 text-right font-mono">{{ $rp($row['ending_tax']) }}</td>
                    </tr>
                @endforeach
                <tr class="font-semibold">
                    <td class="px-5 py-3" colspan="2">Total kabupaten</td>
                    <td class="px-5 py-3 text-right font-mono">{{ $rp($totals['opening_fund']) }}</td>
                    <td class="px-5 py-3 text-right font-mono">{{ $rp($totals['receipt']) }}</td>
                    <td class="px-5 py-3 text-right font-mono">{{ $rp($totals['realization']) }}</td>
                    <td class="px-5 py-3 text-right font-mono">{{ $rp($totals['ending_fund']) }}</td>
                    <td class="px-5 py-3 text-right font-mono">{{ $rp($totals['ending_tax']) }}</td>
                </tr>
            </x-pm.data-table>
        </x-pm.card>
    @endif
@endsection
