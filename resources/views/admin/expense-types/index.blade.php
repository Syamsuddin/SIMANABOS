@extends('layouts.app')

@section('title', 'Jenis Belanja '.$fiscalYear->year)

@section('content')
    <x-pm.page-header :title="'Jenis Belanja '.$fiscalYear->year" description="Klasifikasi belanja untuk tahun anggaran ini. Kode tidak dapat diubah; nonaktifkan bila tidak dipakai lagi." :breadcrumb="['Tahun Anggaran', (string) $fiscalYear->year, 'Jenis Belanja']">
        <x-slot:actions>
            @if ($canEdit)
                <form method="POST" action="{{ route('admin.fiscal-years.expense-types.copy', $fiscalYear) }}">
                    @csrf
                    <x-pm.button type="submit" variant="secondary">Salin dari tahun sebelumnya</x-pm.button>
                </form>
                <x-pm.link-button :href="route('admin.fiscal-years.expense-types.create', $fiscalYear)" icon="plus">Tambah jenis belanja</x-pm.link-button>
            @endif
        </x-slot:actions>
    </x-pm.page-header>

    @unless ($canEdit)
        <x-pm.alert tone="info" class="mt-5">Tahun anggaran ini sudah ditutup; jenis belanja tidak dapat diubah.</x-pm.alert>
    @endunless

    <x-pm.card flush class="mt-5">
        @if ($expenseTypes->isEmpty())
            <x-pm.empty-state icon="wallet" title="Belum ada jenis belanja" description="Tambahkan jenis belanja sesuai juknis tahun ini atau salin dari tahun sebelumnya." />
        @else
            <x-pm.data-table caption="Daftar jenis belanja">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Urutan</th>
                        <th scope="col" class="px-5 py-3">Kode</th>
                        <th scope="col" class="px-5 py-3">Nama</th>
                        <th scope="col" class="px-5 py-3">Kode rekening ARKAS</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($expenseTypes as $expenseType)
                    <tr>
                        <td class="px-5 py-3 font-mono text-mono">{{ $expenseType->sort_order }}</td>
                        <td class="px-5 py-3 font-mono text-mono">{{ $expenseType->code }}</td>
                        <td class="px-5 py-3">{{ $expenseType->name }}</td>
                        <td class="px-5 py-3 font-mono text-mono">{{ $expenseType->arkas_account_code ?? '—' }}</td>
                        <td class="px-5 py-3">
                            @if ($expenseType->is_active)
                                <x-pm.badge tone="success" icon="check-circle">Aktif</x-pm.badge>
                            @else
                                <x-pm.badge tone="neutral" icon="x-circle">Nonaktif</x-pm.badge>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            @if ($canEdit)
                                <x-pm.link-button variant="ghost" size="sm" :href="route('admin.fiscal-years.expense-types.edit', [$fiscalYear, $expenseType])">Ubah</x-pm.link-button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-pm.data-table>
        @endif
    </x-pm.card>
@endsection
