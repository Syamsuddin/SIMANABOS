@extends('layouts.app')

@section('title', 'Buka Tahun Anggaran')

@section('content')
    <x-pm.page-header title="Buka Tahun Anggaran" :breadcrumb="['Tahun Anggaran', 'Buka']" />

    <x-pm.card class="mt-5 max-w-xl">
        <form method="POST" action="{{ route('admin.fiscal-years.store') }}" novalidate class="grid gap-5" x-data="{ busy: false }" @submit="busy = true">
            @csrf

            <x-pm.text-field name="year" type="number" label="Tahun anggaran" required mono :value="$defaultYear" min="2000" max="2100" />
            <x-pm.text-field name="default_deadline_day" type="number" label="Tanggal tenggat bawaan" required mono :value="$defaultDeadlineDay" min="1" max="28" hint="Laporan suatu bulan jatuh tempo pada tanggal ini di bulan berikutnya. Dapat diubah per bulan setelah tahun dibuka." />

            <div class="flex gap-2">
                <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Buka tahun anggaran</x-pm.button>
                <x-pm.link-button variant="secondary" :href="route('admin.fiscal-years.index')">Batal</x-pm.link-button>
            </div>
        </form>
    </x-pm.card>
@endsection
