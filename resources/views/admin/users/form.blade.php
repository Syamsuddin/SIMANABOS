@extends('layouts.app')

@php
    $isNew = ! $account->exists;
    $roleOptions = collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all();
@endphp
@section('title', $isNew ? 'Tambah Akun' : 'Ubah Akun')

@section('content')
    <x-pm.page-header :title="$isNew ? 'Tambah Akun' : 'Ubah Akun'" :breadcrumb="['Akun Pengguna', $isNew ? 'Tambah' : 'Ubah']" />

    <div class="mt-5 grid max-w-xl gap-5">
        <x-pm.card>
            <form method="POST" action="{{ $isNew ? route('admin.users.store') : route('admin.users.update', $account) }}" novalidate class="grid gap-5" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                @unless ($isNew) @method('PUT') @endunless

                @if ($isNew)
                    <x-pm.text-field name="username" label="Nama pengguna" required mono :value="$account->username" maxlength="50" autocomplete="off" />
                @else
                    <x-pm.text-field name="username_view" label="Nama pengguna" mono :value="$account->username" disabled />
                @endif
                <x-pm.text-field name="name" label="Nama lengkap" required :value="$account->name" maxlength="150" />
                <x-pm.select name="role" label="Peran" required :value="$account->roles->first()?->name" :options="$roleOptions" />
                <x-pm.select name="school_id" label="Sekolah" optional :value="$account->school_id" :options="$schools->pluck('name', 'id')->all()" placeholder="Tanpa sekolah" hint="Wajib untuk Operator dan Kepala Sekolah; dikosongkan untuk peran dinas." />
                @if ($isNew)
                    <x-pm.text-field name="password" type="password" label="Sandi sementara" required autocomplete="new-password" hint="Minimal 10 karakter, huruf dan angka. Pengguna wajib menggantinya saat masuk pertama." />
                @else
                    <x-pm.checkbox name="is_active" label="Akun aktif" :checked="$account->is_active" hint="Akun nonaktif tidak dapat masuk." />
                @endif

                <div class="flex gap-2">
                    <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan akun</x-pm.button>
                    <x-pm.link-button variant="secondary" :href="route('admin.users.index')">Batal</x-pm.link-button>
                </div>
            </form>
        </x-pm.card>

        @unless ($isNew)
            <x-pm.card title="Atur ulang sandi" subtitle="Pengguna wajib menggantinya saat masuk berikutnya.">
                <form method="POST" action="{{ route('admin.users.password.reset', $account) }}" novalidate class="grid gap-5" x-data="{ busy: false }" @submit="busy = true">
                    @csrf
                    @method('PUT')
                    <x-pm.text-field name="password" type="password" label="Sandi sementara baru" required autocomplete="new-password" hint="Minimal 10 karakter, huruf dan angka." />
                    <div>
                        <x-pm.button type="submit" variant="secondary" x-bind:disabled="busy" x-bind:aria-busy="busy">Atur ulang sandi</x-pm.button>
                    </div>
                </form>
            </x-pm.card>
        @endunless
    </div>
@endsection
