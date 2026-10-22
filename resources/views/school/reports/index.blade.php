@extends('layouts.app')

@section('title', 'Laporan Bulanan')

@section('content')
    <x-pm.page-header title="Laporan Bulanan" description="Status laporan tiap bulan per sumber dana. Saldo awal bulan terisi otomatis dari saldo akhir bulan sebelumnya." :breadcrumb="['Data sekolah', 'Laporan bulanan']" />

    <x-pm.card class="mt-5">
        <form method="GET" action="{{ route('school.reports.index') }}" class="grid items-end gap-4 sm:grid-cols-[1fr_auto]">
            <x-pm.select name="tahun" label="Tahun anggaran" :options="$fiscalYears->pluck('year', 'year')->all()" :value="$fiscalYear?->year" placeholder="Pilih tahun" />
            <x-pm.button type="submit" variant="secondary">Tampilkan</x-pm.button>
        </form>
    </x-pm.card>

    @if (! $fiscalYear)
        <x-pm.card flush class="mt-5"><x-pm.empty-state icon="calendar" title="Belum ada tahun anggaran" description="Dinas belum membuka tahun anggaran." /></x-pm.card>
    @elseif ($groups->isEmpty())
        <x-pm.card flush class="mt-5"><x-pm.empty-state icon="file-text" title="Belum ada laporan" description="Laporan bulanan dibuat otomatis setelah Dinas menetapkan pagu sekolah Anda untuk tahun {{ $fiscalYear->year }}." /></x-pm.card>
    @else
        @foreach ($groups as $reports)
            <x-pm.card flush class="mt-5" :title="$reports->first()->fundSource->name">
                <x-pm.data-table caption="Laporan bulanan {{ $reports->first()->fundSource->name }}">
                    <x-slot:head>
                        <tr>
                            <th scope="col" class="px-5 py-3">Bulan</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3 text-right">Saldo akhir dana (Rp)</th>
                            <th scope="col" class="px-5 py-3 text-right">Selisih saldo (Rp)</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </x-slot:head>
                    @foreach ($reports as $report)
                        <tr>
                            <td class="px-5 py-3">{{ \Illuminate\Support\Carbon::create(null, $report->month, 1)->locale('id')->translatedFormat('F') }}</td>
                            <td class="px-5 py-3"><x-pm.status-badge :status="$report->status" /></td>
                            <td class="px-5 py-3 text-right font-mono text-mono">{{ $report->computed_ending_fund !== null ? \App\Support\Money::format($report->computed_ending_fund) : '—' }}</td>
                            <td class="px-5 py-3 text-right font-mono text-mono">
                                @if ($report->computed_difference === null)
                                    —
                                @elseif (! \Brick\Math\BigDecimal::of($report->computed_difference)->isZero())
                                    <x-pm.badge tone="warning" icon="alert">{{ \App\Support\Money::format($report->computed_difference) }}</x-pm.badge>
                                @else
                                    {{ \App\Support\Money::format($report->computed_difference) }}
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <x-pm.link-button variant="secondary" :href="route('school.reports.edit', $report)">{{ $report->isEditable() ? 'Isi laporan' : 'Lihat' }}</x-pm.link-button>
                            </td>
                        </tr>
                    @endforeach
                </x-pm.data-table>
            </x-pm.card>
        @endforeach
    @endif
@endsection
