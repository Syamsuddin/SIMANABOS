@extends('layouts.app')

@section('title', 'Saldo Awal')

@section('content')
    <x-pm.page-header title="Saldo Awal" description="Saldo awal Januari (dana dan pajak) disalin dari laporan resmi. Pemeriksa Dinas memverifikasinya; setelah terverifikasi angka terkunci." :breadcrumb="['Data sekolah', 'Saldo awal']" />

    @if ($errors->any())
        <x-pm.alert tone="danger" class="mt-5" title="Saldo awal belum tersimpan">{{ $errors->first() }}</x-pm.alert>
    @endif

    <x-pm.card class="mt-5">
        <form method="GET" action="{{ route('school.opening-balance.index') }}" class="grid items-end gap-4 sm:grid-cols-[1fr_auto]">
            <x-pm.select name="tahun" label="Tahun anggaran" :options="$fiscalYears->pluck('year', 'year')->all()" :value="$fiscalYear?->year" placeholder="Pilih tahun" />
            <x-pm.button type="submit" variant="secondary">Tampilkan</x-pm.button>
        </form>
    </x-pm.card>

    @if (! $fiscalYear)
        <x-pm.card flush class="mt-5"><x-pm.empty-state icon="calendar" title="Belum ada tahun anggaran" description="Dinas belum membuka tahun anggaran." /></x-pm.card>
    @elseif ($rows->isEmpty())
        <x-pm.card flush class="mt-5"><x-pm.empty-state icon="wallet" title="Pagu belum ditetapkan" description="Saldo awal dapat diisi setelah Dinas menetapkan pagu sekolah Anda untuk tahun {{ $fiscalYear->year }}." /></x-pm.card>
    @else
        @foreach ($rows as $row)
            @php
                $balance = $row['balance'];
                $fund = $row['fundSource'];
                $editable = $canEdit && ($balance === null || ($balance->isManual() && ! $balance->isVerified()));
            @endphp
            <x-pm.card class="mt-5 max-w-2xl" :title="$fund->name">
                <x-slot:actions>
                    @if ($balance === null)
                        <x-pm.badge tone="warning" icon="alert">Belum diisi</x-pm.badge>
                    @elseif ($balance->isVerified())
                        <x-pm.badge tone="success" icon="check-circle">Terverifikasi</x-pm.badge>
                    @else
                        <x-pm.badge tone="info" icon="info">Menunggu verifikasi</x-pm.badge>
                    @endif
                </x-slot:actions>

                <form method="POST" action="{{ route('school.opening-balance.update') }}" novalidate class="grid gap-5" x-data="{ busy: false }" @submit="busy = true">
                    @csrf @method('PUT')
                    <input type="hidden" name="fiscal_year_id" value="{{ $fiscalYear->id }}">
                    <input type="hidden" name="fund_source_id" value="{{ $fund->id }}">
                    <x-pm.text-field :id="'fund-'.$fund->id" name="fund_amount" label="Saldo dana (Rp)" mono inputmode="decimal" class="text-right" :disabled="! $editable" required
                        :value="$balance ? \App\Support\Money::format($balance->fund_amount) : null" />
                    <x-pm.text-field :id="'tax-'.$fund->id" name="tax_amount" label="Saldo pajak (Rp)" mono inputmode="decimal" class="text-right" :disabled="! $editable"
                        :value="$balance ? \App\Support\Money::format($balance->tax_amount) : '0,00'" />
                    @if ($editable)
                        <div><x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan saldo awal</x-pm.button></div>
                    @endif
                </form>
            </x-pm.card>
        @endforeach
    @endif
@endsection
