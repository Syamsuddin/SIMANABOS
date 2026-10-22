@extends('layouts.app')

@section('title', 'Daftar Perhatian')

@php
    $monthName = fn ($m) => \Illuminate\Support\Carbon::create(null, $m, 1)->locale('id')->translatedFormat('F');
@endphp

@section('content')
    <x-pm.page-header title="Daftar Perhatian" description="Sekolah yang perlu ditindaklanjuti, dikelompokkan menurut pemicu masalah." :breadcrumb="['Utama', 'Daftar perhatian']" />

    <x-pm.card class="mt-5">
        <form method="GET" action="{{ route('attention.index') }}" class="grid items-end gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-pm.select name="tahun" label="Tahun anggaran" :options="$fiscalYears->pluck('year', 'year')->all()" :value="$fiscalYear?->year" placeholder="Pilih tahun" />
            <x-pm.select name="pemicu" label="Pemicu" :options="$triggers" :value="request('pemicu')" placeholder="Semua pemicu" />
            <x-pm.select name="jenjang" label="Jenjang" :options="['SD' => 'SD', 'SMP' => 'SMP']" :value="request('jenjang')" placeholder="Semua jenjang" />
            <x-pm.select name="kecamatan" label="Kecamatan" :options="$districts" :value="request('kecamatan')" placeholder="Semua kecamatan" />
            <x-pm.button type="submit" variant="secondary">Terapkan</x-pm.button>
        </form>
    </x-pm.card>

    <x-pm.card flush class="mt-5">
        @if ($fiscalYear === null)
            <x-pm.empty-state icon="calendar" title="Belum ada tahun anggaran" description="Buka tahun anggaran terlebih dahulu di menu Pengaturan." />
        @elseif ($rows->isEmpty())
            <x-pm.empty-state icon="check-circle" title="Tidak ada sekolah yang perlu perhatian" description="Tidak ada pemicu masalah pada filter ini." />
        @else
            <x-pm.data-table caption="Sekolah yang perlu perhatian">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Sekolah</th>
                        <th scope="col" class="px-5 py-3">Pemicu</th>
                        <th scope="col" class="px-5 py-3">Bulan</th>
                        <th scope="col" class="px-5 py-3">Sumber dana</th>
                        <th scope="col" class="px-5 py-3">Keterangan</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($rows as $row)
                    <tr>
                        <td class="px-5 py-3">{{ $row['school']->name }}<span class="block font-mono text-small text-ink-muted">{{ $row['school']->npsn }}</span></td>
                        <td class="px-5 py-3"><x-pm.badge :tone="$row['trigger']->tone()" :icon="$row['trigger']->icon()">{{ $row['trigger']->label() }}</x-pm.badge></td>
                        <td class="px-5 py-3">{{ $row['month'] ? $monthName($row['month']) : '—' }}</td>
                        <td class="px-5 py-3">{{ $row['fund_source'] }}</td>
                        <td class="px-5 py-3 text-small">{{ $row['detail'] }}</td>
                        <td class="px-5 py-3 text-right">
                            @if ($row['report_id'] && auth()->user()->hasAnyRole(['pemeriksa_dinas', 'admin_dinas']))
                                <x-pm.link-button variant="secondary" size="sm" :href="route('review.reports.show', $row['report_id'])">Buka</x-pm.link-button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-pm.data-table>
        @endif
    </x-pm.card>
@endsection
