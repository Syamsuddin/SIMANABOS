@extends('layouts.app')

@section('title', 'Tahun Anggaran '.$fiscalYear->year)

@php
    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
@endphp

@section('content')
    <x-pm.page-header :title="'Tahun Anggaran '.$fiscalYear->year" :breadcrumb="['Tahun Anggaran', (string) $fiscalYear->year]">
        <x-slot:actions>
            <x-pm.link-button variant="secondary" :href="route('admin.fiscal-years.expense-types.index', $fiscalYear)">Jenis belanja</x-pm.link-button>
            <x-pm.link-button variant="secondary" :href="route('admin.fiscal-years.document-requirements.index', $fiscalYear)">Dokumen wajib</x-pm.link-button>
            <x-pm.badge :tone="$canEdit ? 'success' : 'neutral'" :icon="$canEdit ? 'check-circle' : 'shield'">{{ $fiscalYear->status->label() }}</x-pm.badge>
        </x-slot:actions>
    </x-pm.page-header>

    @unless ($canEdit)
        <x-pm.alert tone="info" class="mt-5">Tahun anggaran ini sudah ditutup; tahap dan tenggat tidak dapat diubah.</x-pm.alert>
    @endunless

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <x-pm.card title="Tenggat laporan bulanan" subtitle="Zona waktu Asia/Makassar (WITA).">
            <form method="POST" action="{{ route('admin.fiscal-years.deadlines.update', $fiscalYear) }}" novalidate class="grid gap-4" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                @method('PUT')
                @foreach ($fiscalYear->deadlines as $deadline)
                    <x-pm.text-field
                        :name="'due_dates['.$deadline->month.']'"
                        :id="'due_dates_'.$deadline->month"
                        :label="'Laporan '.$months[$deadline->month - 1]"
                        type="date"
                        :value="$deadline->due_date->toDateString()"
                        :error="$errors->first('due_dates.'.$deadline->month)"
                        :disabled="! $canEdit"
                    />
                @endforeach
                @if ($canEdit)
                    <div>
                        <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan tenggat</x-pm.button>
                    </div>
                @endif
            </form>
        </x-pm.card>

        <div class="grid content-start gap-5">
            <x-pm.card title="Tahap pencairan" subtitle="Rentang bulan tiap tahap, dipakai untuk pembanding laporan periodik." flush>
                @if ($fiscalYear->stages->isEmpty())
                    <x-pm.empty-state icon="calendar" title="Belum ada tahap" description="Tambahkan tahap setelah rentang bulan resmi ditetapkan." />
                @else
                    <x-pm.data-table caption="Tahap tahun anggaran">
                        <x-slot:head>
                            <tr>
                                <th scope="col" class="px-5 py-3">Tahap</th>
                                <th scope="col" class="px-5 py-3">Rentang</th>
                                <th scope="col" class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </x-slot:head>
                        @foreach ($fiscalYear->stages as $stage)
                            <tr>
                                <td class="px-5 py-3">{{ $stage->label }}</td>
                                <td class="px-5 py-3">{{ $months[$stage->start_month - 1] }} – {{ $months[$stage->end_month - 1] }}</td>
                                <td class="px-5 py-3 text-right">
                                    @if ($canEdit)
                                        <form method="POST" action="{{ route('admin.fiscal-years.stages.destroy', [$fiscalYear, $stage]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-pm.button type="submit" variant="ghost" size="sm" icon="trash">Hapus {{ $stage->label }}</x-pm.button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </x-pm.data-table>
                @endif
            </x-pm.card>

            @if ($canEdit)
                <x-pm.card title="Tambah tahap">
                    <form method="POST" action="{{ route('admin.fiscal-years.stages.store', $fiscalYear) }}" novalidate class="grid gap-4" x-data="{ busy: false }" @submit="busy = true">
                        @csrf
                        <x-pm.text-field name="stage_no" type="number" label="Nomor tahap" required mono min="1" max="12" />
                        <x-pm.text-field name="label" label="Label" required maxlength="50" hint="Contoh: Tahap I" />
                        <x-pm.text-field name="start_month" type="number" label="Bulan mulai (1–12)" required mono min="1" max="12" />
                        <x-pm.text-field name="end_month" type="number" label="Bulan akhir (1–12)" required mono min="1" max="12" />
                        <div>
                            <x-pm.button type="submit" variant="secondary" x-bind:disabled="busy" x-bind:aria-busy="busy">Tambah tahap</x-pm.button>
                        </div>
                    </form>
                </x-pm.card>
            @endif
        </div>
    </div>
@endsection
