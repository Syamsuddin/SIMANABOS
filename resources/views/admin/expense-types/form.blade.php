@extends('layouts.app')

@php $isNew = ! $expenseType->exists; @endphp
@section('title', $isNew ? 'Tambah Jenis Belanja' : 'Ubah Jenis Belanja')

@section('content')
    <x-pm.page-header :title="$isNew ? 'Tambah Jenis Belanja' : 'Ubah Jenis Belanja'" :breadcrumb="['Tahun Anggaran', (string) $fiscalYear->year, 'Jenis Belanja', $isNew ? 'Tambah' : 'Ubah']" />

    <x-pm.card class="mt-5 max-w-xl">
        <form method="POST" action="{{ $isNew ? route('admin.fiscal-years.expense-types.store', $fiscalYear) : route('admin.fiscal-years.expense-types.update', [$fiscalYear, $expenseType]) }}" novalidate class="grid gap-5" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @unless ($isNew) @method('PUT') @endunless

            @if ($isNew)
                <x-pm.text-field name="code" label="Kode" required mono maxlength="30" hint="Huruf kapital, angka, dan garis bawah. Tidak dapat diubah setelah disimpan." />
            @else
                <x-pm.text-field name="code_display" id="code_display" label="Kode" mono :value="$expenseType->code" disabled hint="Kode tidak dapat diubah." />
            @endif
            <x-pm.text-field name="name" label="Nama jenis belanja" required :value="$expenseType->name" maxlength="150" />
            <x-pm.text-field name="arkas_account_code" label="Kode rekening ARKAS" mono :value="$expenseType->arkas_account_code" maxlength="50" hint="Opsional; isi sesuai daftar resmi dinas." />
            <x-pm.text-field name="sort_order" type="number" label="Urutan tampil" mono :value="$expenseType->sort_order" min="0" />
            <x-pm.checkbox name="is_active" label="Jenis belanja aktif" :checked="$expenseType->is_active" />

            <div class="flex gap-2">
                <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan jenis belanja</x-pm.button>
                <x-pm.link-button variant="secondary" :href="route('admin.fiscal-years.expense-types.index', $fiscalYear)">Batal</x-pm.link-button>
            </div>
        </form>
    </x-pm.card>
@endsection
