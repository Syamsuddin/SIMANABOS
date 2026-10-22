@extends('layouts.app')

@php $isNew = ! $school->exists; @endphp
@section('title', $isNew ? 'Tambah Sekolah' : 'Ubah Sekolah')

@section('content')
    <x-pm.page-header :title="$isNew ? 'Tambah Sekolah' : 'Ubah Sekolah'" :breadcrumb="['Sekolah', $isNew ? 'Tambah' : 'Ubah']" />

    <x-pm.card class="mt-5 max-w-xl">
        <form method="POST" action="{{ $isNew ? route('admin.schools.store') : route('admin.schools.update', $school) }}" novalidate class="grid gap-5" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @unless ($isNew) @method('PUT') @endunless

            <x-pm.text-field name="npsn" label="NPSN" required mono :value="$school->npsn" maxlength="8" inputmode="numeric" hint="8 digit angka." />
            <x-pm.text-field name="name" label="Nama sekolah" required :value="$school->name" maxlength="150" />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-pm.select name="level" label="Jenjang" required :value="$school->level?->value" :options="collect($levels)->mapWithKeys(fn ($l) => [$l->value => $l->label()])->all()" />
                <x-pm.select name="status" label="Status sekolah" required :value="$school->status?->value" :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
            </div>
            <x-pm.select name="district_id" label="Kecamatan" required :value="$school->district_id" :options="$districts->pluck('name', 'id')->all()" />
            <x-pm.text-field name="address" label="Alamat" optional :value="$school->address" maxlength="255" />
            <x-pm.text-field name="phone" label="Telepon" optional :value="$school->phone" maxlength="30" type="tel" />

            <div class="flex gap-2">
                <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan sekolah</x-pm.button>
                <x-pm.link-button variant="secondary" :href="$isNew ? route('admin.schools.index') : route('admin.schools.show', $school)">Batal</x-pm.link-button>
            </div>
        </form>
    </x-pm.card>
@endsection
