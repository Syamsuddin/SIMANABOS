@extends('layouts.app')

@section('title', 'Pagu Alokasi')

@section('content')
    <x-pm.page-header title="Pagu Alokasi" description="Pagu per sekolah dan sumber dana. Menyimpan pagu menyiapkan laporan bulanan berstatus draf mulai bulan aktif sampai Desember." :breadcrumb="['Anggaran', 'Pagu alokasi']" />

    @if ($errors->any())
        <x-pm.alert tone="danger" class="mt-5" title="Pagu belum tersimpan">Periksa kembali isian yang ditandai di bawah.</x-pm.alert>
    @endif

    <x-pm.card class="mt-5">
        <form method="GET" action="{{ route('admin.allocations.index') }}" class="grid items-end gap-4 sm:grid-cols-[1fr_1fr_auto]">
            <x-pm.select name="tahun" label="Tahun anggaran" :options="$fiscalYears->pluck('year', 'year')->all()" :value="$fiscalYear?->year" placeholder="Pilih tahun" />
            <x-pm.select name="sumber_dana" label="Sumber dana" :options="$fundSources->pluck('name', 'id')->all()" :value="$fundSource?->id" placeholder="Pilih sumber dana" />
            <x-pm.button type="submit" variant="secondary">Tampilkan</x-pm.button>
        </form>
    </x-pm.card>

    @if (! $fiscalYear || ! $fundSource)
        <x-pm.card flush class="mt-5">
            <x-pm.empty-state icon="wallet" title="Tahun anggaran atau sumber dana belum tersedia" description="Buka tahun anggaran dan pastikan ada sumber dana aktif sebelum mengisi pagu." />
        </x-pm.card>
    @elseif ($schools->isEmpty())
        <x-pm.card flush class="mt-5">
            <x-pm.empty-state icon="building" title="Belum ada sekolah aktif" description="Tambahkan sekolah terlebih dahulu agar pagu dapat diisi." />
        </x-pm.card>
    @else
        <form method="POST" action="{{ route('admin.allocations.store') }}" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <input type="hidden" name="fiscal_year_id" value="{{ $fiscalYear->id }}">
            <input type="hidden" name="fund_source_id" value="{{ $fundSource->id }}">

            @unless ($canEdit)
                <x-pm.alert tone="info" class="mt-5">Tahun anggaran ini sudah ditutup; pagu tidak dapat diubah.</x-pm.alert>
            @endunless

            <x-pm.card flush class="mt-5" :title="$fundSource->name.' '.$fiscalYear->year">
                <x-pm.data-table caption="Pagu per sekolah">
                    <x-slot:head>
                        <tr>
                            <th scope="col" class="px-5 py-3">NPSN</th>
                            <th scope="col" class="px-5 py-3">Sekolah</th>
                            <th scope="col" class="px-5 py-3 text-right">Pagu (Rp)</th>
                            <th scope="col" class="px-5 py-3">Aktif mulai bulan</th>
                        </tr>
                    </x-slot:head>
                    @foreach ($schools as $school)
                        @php
                            $allocation = $allocations->get($school->id);
                            $amountError = $errors->first("rows.{$school->id}.amount");
                            $monthError = $errors->first("rows.{$school->id}.active_from_month");
                        @endphp
                        <tr>
                            <td class="px-5 py-3 font-mono text-mono">{{ $school->npsn }}</td>
                            <td class="px-5 py-3">{{ $school->name }}</td>
                            <td class="px-5 py-3">
                                <input type="text" inputmode="decimal" autocomplete="off"
                                    name="rows[{{ $school->id }}][amount]"
                                    aria-label="Pagu {{ $school->name }}"
                                    @if ($amountError) aria-invalid="true" @endif
                                    value="{{ old("rows.{$school->id}.amount", $allocation ? \App\Support\Money::format($allocation->amount) : '') }}"
                                    @disabled(! $canEdit)
                                    class="h-control w-48 rounded-md border bg-surface-raised px-3 text-right font-mono text-mono text-ink focus:border-primary focus:outline-2 focus:outline-offset-1 focus:outline-focus disabled:bg-surface-sunken disabled:text-ink-muted {{ $amountError ? 'border-danger' : 'border-line-strong' }}">
                                @if ($amountError)<p class="mt-1 text-small font-medium text-danger">{{ $amountError }}</p>@endif
                            </td>
                            <td class="px-5 py-3">
                                <input type="number" min="1" max="12" name="rows[{{ $school->id }}][active_from_month]"
                                    aria-label="Bulan mulai aktif {{ $school->name }}"
                                    value="{{ old("rows.{$school->id}.active_from_month", $allocation?->active_from_month ?? 1) }}"
                                    @disabled(! $canEdit)
                                    class="h-control w-20 rounded-md border bg-surface-raised px-3 font-mono text-mono text-ink focus:border-primary focus:outline-2 focus:outline-offset-1 focus:outline-focus disabled:bg-surface-sunken disabled:text-ink-muted {{ $monthError ? 'border-danger' : 'border-line-strong' }}">
                                @if ($monthError)<p class="mt-1 text-small font-medium text-danger">{{ $monthError }}</p>@endif
                            </td>
                        </tr>
                    @endforeach
                </x-pm.data-table>
                @if ($canEdit)
                    <x-slot:footer>
                        <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan pagu</x-pm.button>
                    </x-slot:footer>
                @endif
            </x-pm.card>
        </form>
    @endif
@endsection
