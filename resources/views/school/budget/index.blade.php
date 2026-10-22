@extends('layouts.app')

@section('title', 'RKAS')

@section('content')
    <x-pm.page-header title="RKAS" :description="$school->name.' — anggaran per jenis belanja, berversi. Perubahan dibuat sebagai versi baru; versi lama tetap tersimpan.'" :breadcrumb="['Data sekolah', 'RKAS']" />

    <x-pm.card class="mt-5">
        <form method="GET" action="{{ route('school.budget.index') }}" class="grid items-end gap-4 sm:grid-cols-[1fr_auto]">
            <x-pm.select name="tahun" label="Tahun anggaran" :options="$fiscalYears->pluck('year', 'year')->all()" :value="$fiscalYear?->year" placeholder="Pilih tahun" />
            <x-pm.button type="submit" variant="secondary">Tampilkan</x-pm.button>
        </form>
    </x-pm.card>

    @if (! $fiscalYear)
        <x-pm.card flush class="mt-5"><x-pm.empty-state icon="calendar" title="Belum ada tahun anggaran" description="Dinas belum membuka tahun anggaran." /></x-pm.card>
    @elseif ($sections->isEmpty())
        <x-pm.card flush class="mt-5"><x-pm.empty-state icon="wallet" title="Pagu belum ditetapkan" description="Dinas belum mengisi pagu sekolah Anda untuk tahun {{ $fiscalYear->year }}. RKAS dapat diisi setelah pagu tersedia." /></x-pm.card>
    @else
        @foreach ($sections as $section)
            @php $fund = $section['allocation']->fundSource; @endphp
            <x-pm.card flush class="mt-5" :title="$fund->name" :subtitle="'Pagu Rp '.\App\Support\Money::format($section['allocation']->amount)">
                @if ($canEdit)
                    <x-slot:actions>
                        <x-pm.link-button icon="plus" :href="route('school.budget.create', ['tahun' => $fiscalYear->year, 'sumber_dana' => $fund->id])">
                            {{ $section['versions']->isEmpty() ? 'Isi RKAS versi 1' : 'Buat versi perubahan' }}
                        </x-pm.link-button>
                    </x-slot:actions>
                @endif

                @if ($section['warning'])
                    <div class="p-5 pb-0">
                        <x-pm.alert tone="warning" title="Anggaran melebihi pagu">
                            Total versi terbaru Rp {{ \App\Support\Money::format($section['warning']['total']) }} melebihi pagu ditambah saldo awal Rp {{ \App\Support\Money::format($section['warning']['ceiling']) }}. Simpan tetap diizinkan.
                        </x-pm.alert>
                    </div>
                @endif

                @if ($section['versions']->isEmpty())
                    <x-pm.empty-state icon="wallet" title="Belum ada RKAS" description="Isi RKAS versi 1 per jenis belanja untuk sumber dana ini." />
                @else
                    <x-pm.data-table caption="Versi RKAS {{ $fund->name }}">
                        <x-slot:head>
                            <tr>
                                <th scope="col" class="px-5 py-3">Versi</th>
                                <th scope="col" class="px-5 py-3">Berlaku</th>
                                <th scope="col" class="px-5 py-3">Alasan</th>
                                <th scope="col" class="px-5 py-3 text-right">Total (Rp)</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($section['versions'] as $row)
                            @php $version = $row['version']; @endphp
                            <tr>
                                <td class="px-5 py-3 font-mono text-mono">{{ $version->version_no }}</td>
                                <td class="px-5 py-3">{{ $version->effective_date->format('d-m-Y') }}</td>
                                <td class="px-5 py-3">{{ $version->reason ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-mono text-mono">{{ \App\Support\Money::format($row['total']) }}</td>
                            </tr>
                            @foreach ($version->lines as $line)
                                <tr class="bg-surface">
                                    <td class="px-5 py-2"></td>
                                    <td class="px-5 py-2 text-small text-ink-muted" colspan="2">{{ $line->expenseType->name }}</td>
                                    <td class="px-5 py-2 text-right font-mono text-mono text-ink-muted">{{ \App\Support\Money::format($line->amount) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </x-pm.data-table>
                @endif
            </x-pm.card>
        @endforeach
    @endif
@endsection
