@extends('layouts.app')

@section('title', 'Ubah Profil Sekolah')

@section('content')
    <x-pm.page-header title="Ubah Profil Sekolah" :breadcrumb="['Profil Sekolah', 'Ubah']" />

    <x-pm.card class="mt-5 max-w-xl">
        <form method="POST" action="{{ route('school.profile.update') }}" novalidate class="grid gap-5" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @method('PUT')
            <x-pm.text-field name="address" label="Alamat" optional :value="$school->address" maxlength="255" />
            <x-pm.text-field name="phone" label="Telepon" optional :value="$school->phone" maxlength="30" type="tel" />
            <div class="flex gap-2">
                <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan profil</x-pm.button>
                <x-pm.link-button variant="secondary" :href="route('school.profile.show')">Batal</x-pm.link-button>
            </div>
        </form>
    </x-pm.card>
@endsection
