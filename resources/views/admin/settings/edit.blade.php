@extends('layouts.app')

@section('title', 'Pengaturan Aplikasi')

@section('content')
    <x-pm.page-header title="Pengaturan Aplikasi" description="Pengaturan yang berlaku untuk seluruh sekolah." :breadcrumb="['Pengaturan', 'Aplikasi']" />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf @method('PUT')
        <x-pm.card title="Persetujuan kepala sekolah" subtitle="Bila aktif, operator hanya dapat mengajukan laporan yang sudah disetujui kepala sekolah. Perubahan angka setelah disetujui membatalkan persetujuan. Laporan yang sudah diajukan tidak terpengaruh.">
            <x-pm.checkbox name="require_principal_approval" label="Wajibkan persetujuan kepala sekolah sebelum laporan diajukan" :checked="$requirePrincipalApproval" />
        </x-pm.card>

        <x-pm.card title="Tenggat dan pengingat" subtitle="Tanggal tenggat bawaan hanya berlaku untuk tahun anggaran yang dibuka berikutnya; tenggat tahun yang sudah dibuka diubah per bulan di halaman tahun anggaran." class="mt-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-pm.text-field name="default_deadline_day" type="number" label="Tanggal tenggat bawaan" required mono :value="$defaultDeadlineDay" min="1" max="28" hint="Laporan suatu bulan jatuh tempo pada tanggal ini di bulan berikutnya (1–28)." />
                <x-pm.text-field name="reminder_days_before" type="number" label="Pengingat sebelum tenggat (hari)" required mono :value="$reminderDaysBefore" min="1" max="14" hint="Operator yang laporannya masih draf diingatkan sekian hari sebelum tenggat." />
            </div>
            <x-slot:footer>
                <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan pengaturan</x-pm.button>
            </x-slot:footer>
        </x-pm.card>
    </form>
@endsection
