@extends('layouts.app')

@section('title', 'Tahun Anggaran')

@section('content')
    <x-pm.page-header title="Tahun Anggaran" description="Membuka tahun anggaran membuat 12 tenggat bulanan dari tanggal tenggat bawaan.">
        <x-slot:actions>
            <x-pm.link-button :href="route('admin.fiscal-years.create')" icon="plus">Buka tahun anggaran</x-pm.link-button>
        </x-slot:actions>
    </x-pm.page-header>

    <x-pm.card flush class="mt-5">
        @if ($fiscalYears->isEmpty())
            <x-pm.empty-state icon="calendar" title="Belum ada tahun anggaran" description="Buka tahun anggaran untuk mulai menyiapkan pagu dan laporan bulanan.">
                <x-slot:actions>
                    <x-pm.link-button :href="route('admin.fiscal-years.create')" icon="plus">Buka tahun anggaran</x-pm.link-button>
                </x-slot:actions>
            </x-pm.empty-state>
        @else
            <x-pm.data-table caption="Daftar tahun anggaran">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Tahun</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3">Tanggal tenggat bawaan</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($fiscalYears as $fiscalYear)
                    <tr>
                        <td class="px-5 py-3 font-mono text-mono">{{ $fiscalYear->year }}</td>
                        <td class="px-5 py-3">
                            <x-pm.badge :tone="$fiscalYear->status === \App\Enums\FiscalYearStatus::Open ? 'success' : 'neutral'" :icon="$fiscalYear->status === \App\Enums\FiscalYearStatus::Open ? 'check-circle' : 'shield'">{{ $fiscalYear->status->label() }}</x-pm.badge>
                        </td>
                        <td class="px-5 py-3">Setiap tanggal {{ $fiscalYear->default_deadline_day }}</td>
                        <td class="px-5 py-3 text-right">
                            <x-pm.link-button variant="ghost" size="sm" :href="route('admin.fiscal-years.show', $fiscalYear)">Lihat</x-pm.link-button>
                        </td>
                    </tr>
                @endforeach
            </x-pm.data-table>
        @endif
    </x-pm.card>
@endsection
