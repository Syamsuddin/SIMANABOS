@extends('layouts.app')

@section('title', 'Dokumen Wajib '.$fiscalYear->year)

@php
    $kinds = \App\Enums\ReportKind::cases();
    $statusOptions = ['all' => 'Semua status', 'negeri' => 'Negeri', 'swasta' => 'Swasta'];
@endphp

@section('content')
    <x-pm.page-header :title="'Dokumen Wajib '.$fiscalYear->year" description="Aturan dokumen yang harus dilampirkan sekolah sebelum laporan diajukan. Isi aturan ditetapkan dinas; aplikasi tidak menebaknya." :breadcrumb="['Tahun Anggaran', (string) $fiscalYear->year, 'Dokumen Wajib']">
        <x-slot:actions>
            @if ($canEdit)
                <form method="POST" action="{{ route('admin.fiscal-years.document-requirements.copy', $fiscalYear) }}">
                    @csrf
                    <x-pm.button type="submit" variant="secondary">Salin dari tahun sebelumnya</x-pm.button>
                </form>
            @endif
        </x-slot:actions>
    </x-pm.page-header>

    @unless ($canEdit)
        <x-pm.alert tone="info" class="mt-5">Tahun anggaran ini sudah ditutup; aturan dokumen tidak dapat diubah.</x-pm.alert>
    @endunless

    @if ($errors->any())
        <x-pm.alert tone="danger" class="mt-5" title="Aturan belum tersimpan">{{ $errors->first() }}</x-pm.alert>
    @endif

    <x-pm.card flush class="mt-5">
        @if ($requirements->isEmpty())
            <x-pm.empty-state icon="file-text" title="Belum ada aturan dokumen" description="Tambahkan aturan sesuai juknis tahun ini atau salin dari tahun sebelumnya. Tanpa aturan, kelengkapan dokumen tidak diperiksa." />
        @else
            <x-pm.data-table caption="Aturan dokumen wajib">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Jenis dokumen</th>
                        <th scope="col" class="px-5 py-3">Untuk</th>
                        <th scope="col" class="px-5 py-3">Status sekolah</th>
                        <th scope="col" class="px-5 py-3">Sumber dana</th>
                        <th scope="col" class="px-5 py-3">Sifat</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($requirements as $requirement)
                    <tr>
                        <td class="px-5 py-3">{{ $requirement->documentType->name }}</td>
                        <td class="px-5 py-3">{{ $requirement->report_kind->label() }}</td>
                        <td class="px-5 py-3">{{ $requirement->statusLabel() }}</td>
                        <td class="px-5 py-3">{{ $requirement->fundSource?->name ?? 'Semua sumber dana' }}</td>
                        <td class="px-5 py-3">
                            @if ($requirement->is_required)
                                <x-pm.badge tone="warning" icon="alert">Wajib</x-pm.badge>
                            @else
                                <x-pm.badge tone="neutral">Tidak wajib</x-pm.badge>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            @if ($canEdit)
                                <x-pm.modal :id="'delete-req-'.$requirement->id" title="Hapus aturan dokumen?" :action="route('admin.fiscal-years.document-requirements.destroy', [$fiscalYear, $requirement])" method="DELETE" tone="danger" trigger="Hapus" triggerVariant="ghost" triggerSize="sm" confirm="Hapus aturan">
                                    Aturan "{{ $requirement->documentType->name }}" tidak lagi diperiksa saat laporan diajukan.
                                </x-pm.modal>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-pm.data-table>
        @endif
    </x-pm.card>

    @if ($canEdit)
        <x-pm.card class="mt-5" title="Tambah aturan">
            <form method="POST" action="{{ route('admin.fiscal-years.document-requirements.store', $fiscalYear) }}" novalidate class="grid gap-4 sm:grid-cols-2" x-data="{ busy: false }" @submit="busy = true">
                @csrf
                <x-pm.select name="document_type_id" label="Jenis dokumen" required :options="$documentTypes->pluck('name', 'id')->all()" />
                <x-pm.select name="report_kind" label="Berlaku untuk" required :options="collect($kinds)->mapWithKeys(fn ($k) => [$k->value => $k->label()])->all()" value="monthly" />
                <x-pm.select name="school_status" label="Status sekolah" required :options="$statusOptions" value="all" />
                <x-pm.select name="fund_source_id" label="Sumber dana" optional placeholder="Semua sumber dana" :options="$fundSources->pluck('name', 'id')->all()" />
                <div class="sm:col-span-2"><x-pm.checkbox name="is_required" label="Wajib dilampirkan" :checked="true" /></div>
                <div class="sm:col-span-2"><x-pm.button type="submit" variant="primary" icon="plus" x-bind:disabled="busy" x-bind:aria-busy="busy">Tambah aturan</x-pm.button></div>
            </form>
        </x-pm.card>
    @endif
@endsection
