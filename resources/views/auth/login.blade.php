@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div>
        <h1 class="text-h1 text-ink">Masuk</h1>
        <p class="mt-1 text-body text-ink-muted">Masukkan username dan sandi Anda.</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" novalidate class="grid gap-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf

        <x-pm.text-field name="username" label="Username" icon="user" required autocomplete="username" autofocus />
        <x-pm.text-field name="password" type="password" label="Sandi" icon="shield" required autocomplete="current-password" />

        <x-pm.button type="submit" variant="primary" size="lg" block x-bind:disabled="busy" x-bind:aria-busy="busy">Masuk</x-pm.button>
    </form>
@endsection
