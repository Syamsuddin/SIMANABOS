@extends('layouts.app')

@php $isNew = ! $fundSource->exists; @endphp
@section('title', $isNew ? 'Tambah Sumber Dana' : 'Ubah Sumber Dana')

@section('content')
    <x-pm.page-header :title="$isNew ? 'Tambah Sumber Dana' : 'Ubah Sumber Dana'" :breadcrumb="['Sumber Dana', $isNew ? 'Tambah' : 'Ubah']" />

    <x-pm.card class="mt-5 max-w-xl">
        <form method="POST" action="{{ $isNew ? route('admin.fund-sources.store') : route('admin.fund-sources.update', $fundSource) }}" novalidate class="grid gap-5" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @unless ($isNew) @method('PUT') @endunless

            @if ($isNew)
                <x-pm.text-field name="code" label="Kode" required mono maxlength="20" hint="Huruf kapital, angka, dan garis bawah. Tidak dapat diubah setelah disimpan." />
            @else
                <x-pm.text-field name="code_display" id="code_display" label="Kode" mono :value="$fundSource->code" disabled hint="Kode tidak dapat diubah." />
            @endif
            <x-pm.text-field name="name" label="Nama sumber dana" required :value="$fundSource->name" maxlength="100" />
            <x-pm.checkbox name="is_active" label="Sumber dana aktif" :checked="$fundSource->is_active" />

            <div class="flex gap-2">
                <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan sumber dana</x-pm.button>
                <x-pm.link-button variant="secondary" :href="route('admin.fund-sources.index')">Batal</x-pm.link-button>
            </div>
        </form>
    </x-pm.card>
@endsection
