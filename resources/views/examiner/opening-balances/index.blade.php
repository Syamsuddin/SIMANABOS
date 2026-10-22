@extends('layouts.app')

@section('title', 'Verifikasi Saldo Awal')

@section('content')
    <x-pm.page-header title="Verifikasi Saldo Awal" description="Cocokkan saldo awal Januari yang diisi sekolah dengan laporan resmi, lalu verifikasi. Januari belum dapat dinyatakan selesai sebelum saldo awal terverifikasi." :breadcrumb="['Pemeriksaan', 'Saldo awal']" />

    <x-pm.card class="mt-5">
        <form method="GET" action="{{ route('examiner.opening-balances.index') }}" class="grid items-end gap-4 sm:grid-cols-[1fr_1fr_auto]">
            <x-pm.select name="tahun" label="Tahun anggaran" :options="$fiscalYears->pluck('year', 'year')->all()" :value="$fiscalYear?->year" placeholder="Pilih tahun" />
            <x-pm.select name="status" label="Status" :options="['menunggu' => 'Menunggu verifikasi', 'semua' => 'Semua']" :value="$onlyPending ? 'menunggu' : 'semua'" placeholder="Pilih status" />
            <x-pm.button type="submit" variant="secondary">Tampilkan</x-pm.button>
        </form>
    </x-pm.card>

    <x-pm.card flush class="mt-5">
        @if ($balances->isEmpty())
            <x-pm.empty-state icon="check-circle" :title="$onlyPending ? 'Tidak ada saldo awal yang menunggu' : 'Belum ada saldo awal'" description="Saldo awal muncul di sini setelah operator sekolah mengisinya." />
        @else
            <x-pm.data-table caption="Saldo awal sekolah">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Sekolah</th>
                        <th scope="col" class="px-5 py-3">Sumber dana</th>
                        <th scope="col" class="px-5 py-3 text-right">Saldo dana (Rp)</th>
                        <th scope="col" class="px-5 py-3 text-right">Saldo pajak (Rp)</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($balances as $balance)
                    <tr>
                        <td class="px-5 py-3">{{ $balance->school->name }}<span class="block font-mono text-small text-ink-muted">{{ $balance->school->npsn }}</span></td>
                        <td class="px-5 py-3">{{ $balance->fundSource->name }}</td>
                        <td class="px-5 py-3 text-right font-mono text-mono">{{ \App\Support\Money::format($balance->fund_amount) }}</td>
                        <td class="px-5 py-3 text-right font-mono text-mono">{{ \App\Support\Money::format($balance->tax_amount) }}</td>
                        <td class="px-5 py-3">
                            @if ($balance->isVerified())
                                <x-pm.badge tone="success" icon="check-circle">Terverifikasi</x-pm.badge>
                            @else
                                <x-pm.badge tone="warning" icon="alert">Menunggu</x-pm.badge>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            @can('verify', $balance)
                                <x-pm.modal :id="'verify-'.$balance->id" title="Verifikasi saldo awal?" :action="route('examiner.opening-balances.verify', $balance)" trigger="Verifikasi" triggerSize="sm" confirm="Verifikasi">
                                    Angka saldo awal {{ $balance->school->name }} akan dikunci dan tidak dapat diubah operator sekolah.
                                </x-pm.modal>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </x-pm.data-table>
        @endif
    </x-pm.card>
@endsection
