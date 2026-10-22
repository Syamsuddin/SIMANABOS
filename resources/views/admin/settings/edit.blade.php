@extends('layouts.app')

@section('title', 'Pengaturan Aplikasi')

@section('content')
    <x-pm.page-header title="Pengaturan Aplikasi" description="Pengaturan yang berlaku untuk seluruh sekolah." :breadcrumb="['Pengaturan', 'Aplikasi']" />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf @method('PUT')
        <x-pm.card title="Persetujuan kepala sekolah" subtitle="Bila aktif, operator hanya dapat mengajukan laporan yang sudah disetujui kepala sekolah. Perubahan angka setelah disetujui membatalkan persetujuan. Laporan yang sudah diajukan tidak terpengaruh.">
            <x-pm.checkbox name="require_principal_approval" label="Wajibkan persetujuan kepala sekolah sebelum laporan diajukan" :checked="$requirePrincipalApproval" />
            <x-slot:footer>
                <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan pengaturan</x-pm.button>
            </x-slot:footer>
        </x-pm.card>
    </form>
@endsection
