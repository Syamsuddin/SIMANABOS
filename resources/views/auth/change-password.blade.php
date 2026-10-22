@extends('layouts.app')

@section('title', 'Ganti Sandi')

@section('content')
    <x-pm.page-header title="Ganti Sandi" description="Gunakan sandi yang tidak dipakai di layanan lain." />

    @if (session('warning'))
        <x-pm.alert tone="warning">{{ session('warning') }}</x-pm.alert>
    @endif

    <x-pm.card class="max-w-xl" title="Sandi baru">
        <form method="POST" action="{{ route('password.update') }}" novalidate class="grid gap-5" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @method('PUT')

            <x-pm.text-field name="current_password" type="password" label="Sandi saat ini" required autocomplete="current-password" />
            <x-pm.text-field name="password" type="password" label="Sandi baru" required autocomplete="new-password" hint="Minimal 10 karakter, kombinasi huruf dan angka." />
            <x-pm.text-field name="password_confirmation" type="password" label="Ulangi sandi baru" required autocomplete="new-password" />

            <div>
                <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Ganti sandi</x-pm.button>
            </div>
        </form>
    </x-pm.card>
@endsection
