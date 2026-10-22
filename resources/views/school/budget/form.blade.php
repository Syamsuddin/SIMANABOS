@extends('layouts.app')

@php $nextNo = ($latest?->version_no ?? 0) + 1; @endphp
@section('title', 'RKAS Versi '.$nextNo)

@section('content')
    <x-pm.page-header :title="'RKAS Versi '.$nextNo" :description="$fundSource->name.' — tahun '.$fiscalYear->year" :breadcrumb="['Data sekolah', 'RKAS', 'Versi '.$nextNo]" />

    @if ($errors->any())
        <x-pm.alert tone="danger" class="mt-5" title="RKAS belum tersimpan">Periksa kembali isian yang ditandai di bawah.</x-pm.alert>
    @endif

    @if ($expenseTypes->isEmpty())
        <x-pm.card flush class="mt-5"><x-pm.empty-state icon="wallet" title="Jenis belanja belum tersedia" description="Dinas belum menetapkan jenis belanja aktif untuk tahun {{ $fiscalYear->year }}." /></x-pm.card>
    @else
        <x-pm.card class="mt-5 max-w-2xl">
            <form method="POST" action="{{ route('school.budget.store') }}" novalidate class="grid gap-5" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                <input type="hidden" name="fiscal_year_id" value="{{ $fiscalYear->id }}">
                <input type="hidden" name="fund_source_id" value="{{ $fundSource->id }}">

                <x-pm.text-field name="effective_date" type="date" label="Berlaku mulai" required :value="$latest ? null : $fiscalYear->year.'-01-01'" hint="Persentase realisasi tiap bulan memakai versi yang berlaku pada hari terakhir bulan itu." />

                @if ($latest)
                    <x-pm.text-field name="reason" label="Alasan perubahan" required maxlength="255" />
                @endif

                <fieldset class="grid gap-4">
                    <legend class="text-small font-semibold text-ink">Anggaran per jenis belanja (Rp)</legend>
                    @foreach ($expenseTypes as $type)
                        <x-pm.text-field
                            :name="'lines['.$type->id.']'"
                            :id="'line-'.$type->id"
                            :label="$type->name"
                            mono inputmode="decimal" autocomplete="off" class="text-right"
                            :value="isset($previous[$type->id]) ? \App\Support\Money::format($previous[$type->id]) : '0,00'"
                        />
                    @endforeach
                    @error('lines')<p class="text-small font-medium text-danger">{{ $message }}</p>@enderror
                </fieldset>

                <div class="flex gap-2">
                    <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan RKAS</x-pm.button>
                    <x-pm.link-button variant="secondary" :href="route('school.budget.index', ['tahun' => $fiscalYear->year])">Batal</x-pm.link-button>
                </div>
            </form>
        </x-pm.card>
    @endif
@endsection
