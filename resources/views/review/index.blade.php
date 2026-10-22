@extends('layouts.app')

@section('title', 'Antrean Pemeriksaan')

@php
    $monthName = fn ($m) => \Illuminate\Support\Carbon::create(null, $m, 1)->locale('id')->translatedFormat('F');
@endphp

@section('content')
    <x-pm.page-header title="Antrean Pemeriksaan" description="Laporan bulanan yang diajukan sekolah. Ambil laporan untuk memeriksanya; hanya satu pemeriksa yang dapat mengambil satu laporan." :breadcrumb="['Pemeriksaan', 'Antrean']" />

    <x-pm.card class="mt-5">
        <form method="GET" action="{{ route('review.reports.index') }}" class="grid items-end gap-4 sm:grid-cols-[1fr_1fr_auto]">
            <x-pm.select name="tahun" label="Tahun anggaran" :options="$fiscalYears->pluck('year', 'year')->all()" :value="$fiscalYear?->year" placeholder="Pilih tahun" />
            <x-pm.select name="status" label="Status" :options="['diajukan' => 'Menunggu diperiksa', 'dalam_pemeriksaan' => 'Dalam pemeriksaan', 'perlu_perbaikan' => 'Perlu perbaikan', 'selesai_diperiksa' => 'Selesai diperiksa']" :value="$filter" placeholder="Pilih status" />
            <x-pm.button type="submit" variant="secondary">Tampilkan</x-pm.button>
        </form>
    </x-pm.card>

    <x-pm.card flush class="mt-5">
        @if ($reports->isEmpty())
            <x-pm.empty-state icon="check-circle" title="Tidak ada laporan pada status ini" description="Laporan muncul di sini setelah sekolah mengajukannya." />
        @else
            <x-pm.data-table caption="Laporan bulanan untuk diperiksa">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Sekolah</th>
                        <th scope="col" class="px-5 py-3">Bulan</th>
                        <th scope="col" class="px-5 py-3">Sumber dana</th>
                        <th scope="col" class="px-5 py-3">Diajukan</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3">Pemeriksa</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($reports as $report)
                    <tr>
                        <td class="px-5 py-3">{{ $report->school->name }}<span class="block font-mono text-small text-ink-muted">{{ $report->school->npsn }}</span></td>
                        <td class="px-5 py-3">{{ $monthName($report->month) }}</td>
                        <td class="px-5 py-3">{{ $report->fundSource->name }}</td>
                        <td class="px-5 py-3">{{ $report->submitted_at?->timezone('Asia/Makassar')->translatedFormat('d M Y') ?? '—' }}</td>
                        <td class="px-5 py-3"><x-pm.status-badge :status="$report->status" /></td>
                        <td class="px-5 py-3">{{ $report->reviewer?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right"><x-pm.link-button variant="secondary" size="sm" :href="route('review.reports.show', $report)">Buka</x-pm.link-button></td>
                    </tr>
                @endforeach
            </x-pm.data-table>
        @endif
    </x-pm.card>
@endsection
