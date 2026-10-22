@extends('layouts.app')

@section('title', 'Laporan Periodik')

@section('content')
    <x-pm.page-header title="Laporan Periodik" description="Angka laporan Tahap dan Tahunan yang dilaporkan sekolah dibandingkan dengan Σ realisasi neto bulanan. Hanya pembanding; tidak masuk dasbor." :breadcrumb="['Utama', 'Laporan periodik']" />

    <x-pm.card class="mt-5">
        <form method="GET" action="{{ route('periodic.index') }}" class="grid items-end gap-4 sm:grid-cols-[1fr_auto]">
            <x-pm.select name="tahun" label="Tahun anggaran" :options="$fiscalYears->pluck('year', 'year')->all()" :value="$fiscalYear?->year" placeholder="Pilih tahun" />
            <x-pm.button type="submit" variant="secondary">Tampilkan</x-pm.button>
        </form>
    </x-pm.card>

    @if (! $fiscalYear)
        <x-pm.card flush class="mt-5"><x-pm.empty-state icon="calendar" title="Belum ada tahun anggaran" description="Belum ada tahun anggaran yang dibuka." /></x-pm.card>
    @elseif ($reports->isEmpty())
        <x-pm.card flush class="mt-5"><x-pm.empty-state icon="file-text" title="Belum ada laporan periodik" description="Belum ada sekolah yang mengisi laporan Tahap atau Tahunan untuk tahun {{ $fiscalYear->year }}." /></x-pm.card>
    @else
        <x-pm.card flush class="mt-5">
            <x-pm.data-table caption="Laporan periodik tahun {{ $fiscalYear->year }}">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Sekolah</th>
                        <th scope="col" class="px-5 py-3">Sumber dana</th>
                        <th scope="col" class="px-5 py-3">Jenis</th>
                        <th scope="col" class="px-5 py-3 text-right">Σ bulanan (Rp)</th>
                        <th scope="col" class="px-5 py-3 text-right">Dilaporkan (Rp)</th>
                        <th scope="col" class="px-5 py-3">Selisih</th>
                        <th scope="col" class="px-5 py-3">Dokumen</th>
                    </tr>
                </x-slot:head>
                @foreach ($reports as $report)
                    @php $c = $comparisons[$report->id]; @endphp
                    <tr>
                        <td class="px-5 py-3">{{ $report->school->name }}</td>
                        <td class="px-5 py-3">{{ $report->fundSource->name }}</td>
                        <td class="px-5 py-3">{{ $report->kind->label() }}@if ($report->stage_no) {{ $report->stage_no }}@endif</td>
                        <td class="px-5 py-3 text-right font-mono text-mono">{{ $c ? \App\Support\Money::format($c['monthly_total']) : '—' }}</td>
                        <td class="px-5 py-3 text-right font-mono text-mono">{{ $c && $c['reported'] !== null ? \App\Support\Money::format($c['reported']) : '—' }}</td>
                        <td class="px-5 py-3">
                            @if ($c === null || $c['difference'] === null)
                                —
                            @elseif (\Brick\Math\BigDecimal::of($c['difference'])->isZero())
                                <x-pm.badge tone="success" icon="check-circle">Sesuai</x-pm.badge>
                            @else
                                <x-pm.badge tone="warning" icon="alert">{{ \App\Support\Money::format($c['difference']) }}</x-pm.badge>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            @forelse ($report->documents as $document)
                                <a class="text-primary underline" href="{{ route('documents.download', $document) }}">{{ $document->currentVersion->original_name }}</a>@if (! $loop->last), @endif
                            @empty
                                —
                            @endforelse
                        </td>
                    </tr>
                @endforeach
            </x-pm.data-table>
        </x-pm.card>
    @endif
@endsection
