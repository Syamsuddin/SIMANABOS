@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    <x-pm.page-header title="Beranda" :description="'Selamat datang, '.auth()->user()->name.'.'" />

    <x-pm.card class="max-w-xl" title="Akun Anda">
        <x-pm.description-list :items="[
            ['label' => 'Nama', 'value' => auth()->user()->name],
            ['label' => 'Username', 'value' => auth()->user()->username, 'mono' => true],
            ['label' => 'Peran', 'value' => auth()->user()->getRoleNames()->map(fn ($role) => \App\Enums\RoleName::tryFrom($role)?->label() ?? $role)->implode(', ')],
            ['label' => 'Waktu masuk', 'value' => auth()->user()->last_login_at?->translatedFormat('j F Y, H.i').' WITA'],
        ]" />
    </x-pm.card>
@endsection
