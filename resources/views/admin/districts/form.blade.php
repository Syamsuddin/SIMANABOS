@extends('layouts.app')

@php $isNew = ! $district->exists; @endphp
@section('title', $isNew ? 'Tambah Kecamatan' : 'Ubah Kecamatan')

@section('content')
    <x-pm.page-header :title="$isNew ? 'Tambah Kecamatan' : 'Ubah Kecamatan'" :breadcrumb="['Kecamatan', $isNew ? 'Tambah' : 'Ubah']" />

    <x-pm.card class="mt-5 max-w-xl">
        <form method="POST" action="{{ $isNew ? route('admin.districts.store') : route('admin.districts.update', $district) }}" novalidate class="grid gap-5" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @unless ($isNew) @method('PUT') @endunless

            <x-pm.text-field name="code" label="Kode" required mono :value="$district->code" maxlength="10" />
            <x-pm.text-field name="name" label="Nama kecamatan" required :value="$district->name" maxlength="100" />

            <div class="flex gap-2">
                <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan kecamatan</x-pm.button>
                <x-pm.link-button variant="secondary" :href="route('admin.districts.index')">Batal</x-pm.link-button>
            </div>
        </form>
    </x-pm.card>
@endsection
