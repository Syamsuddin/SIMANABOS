@extends('layouts.app')

@php
    $money = fn ($v) => $v === null ? null : \App\Support\Money::format($v);
    $monthName = \Illuminate\Support\Carbon::create(null, $report->month, 1)->locale('id')->translatedFormat('F');
    $hasDifference = $balance['difference'] !== null && ! \Brick\Math\BigDecimal::of($balance['difference'])->isZero();
    $disabled = ! $canEdit;
@endphp

@section('title', 'Laporan '.$monthName)

@section('content')
    <x-pm.page-header :title="'Laporan '.$monthName.' '.$report->fiscalYear->year" :description="$report->fundSource->name" :breadcrumb="['Data sekolah', 'Laporan bulanan', $monthName]">
        <x-slot:actions><x-pm.status-badge :status="$report->status" /></x-slot:actions>
    </x-pm.page-header>

    @if ($errors->any())
        <x-pm.alert tone="danger" class="mt-5" title="Laporan belum tersimpan">{{ $errors->first() }}</x-pm.alert>
    @endif

    @unless ($canEdit)
        <x-pm.alert tone="info" class="mt-5" title="Laporan hanya dapat dilihat">Angka hanya dapat diubah operator sekolah pada laporan berstatus Draf atau Perlu perbaikan.</x-pm.alert>
    @endunless

    <div class="mt-5 grid items-start gap-5 wide:grid-cols-[2fr_1fr]">
        <form method="POST" action="{{ route('school.reports.update', $report) }}" novalidate class="grid gap-5" x-data="{ busy: false }" @submit="busy = true">
            @csrf @method('PUT')
            <input type="hidden" name="lock_version" value="{{ $report->lock_version }}">

            <x-pm.card title="Saldo awal" subtitle="Terisi otomatis dari saldo akhir bulan sebelumnya; tidak dapat diubah.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-pm.text-field id="opening_fund" name="opening_fund" label="Saldo awal dana (Rp)" mono class="text-right" disabled :value="$money($report->opening_fund) ?? '0,00'" />
                    <x-pm.text-field id="opening_tax" name="opening_tax" label="Saldo awal pajak (Rp)" mono class="text-right" disabled :value="$money($report->opening_tax) ?? '0,00'" />
                </div>
            </x-pm.card>

            <x-pm.card title="Penerimaan">
                <div class="grid gap-4">
                    <x-pm.checkbox name="is_nil" label="Laporan nihil (tanpa penerimaan dan realisasi)" :checked="old('is_nil', $report->is_nil)" :disabled="$disabled" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-pm.text-field name="receipt_bos" label="Penerimaan dana BOS (Rp)" mono inputmode="decimal" autocomplete="off" class="text-right" :disabled="$disabled" :value="$money($report->receipt_bos) ?? '0,00'" />
                        <x-pm.text-field name="receipt_other" label="Penerimaan lain (Rp)" mono inputmode="decimal" autocomplete="off" class="text-right" :disabled="$disabled" :value="$money($report->receipt_other) ?? '0,00'" />
                    </div>
                    <x-pm.text-field name="receipt_other_note" label="Keterangan penerimaan lain" optional maxlength="255" :disabled="$disabled" :value="$report->receipt_other_note" hint="Wajib diisi saat mengajukan bila ada penerimaan lain." />
                </div>
            </x-pm.card>

            <x-pm.card title="Realisasi belanja per jenis belanja" subtitle="Pengembalian belanja mengurangi realisasi jenis asalnya.">
                @if ($expenseTypes->isEmpty())
                    <x-pm.empty-state icon="wallet" title="Jenis belanja belum tersedia" description="Dinas belum menetapkan jenis belanja aktif untuk tahun {{ $report->fiscalYear->year }}." />
                @else
                    <div class="grid gap-5">
                        @foreach ($expenseTypes as $type)
                            <fieldset class="grid gap-3 sm:grid-cols-2">
                                <legend class="mb-2 text-small font-semibold text-ink">{{ $type->name }}</legend>
                                <x-pm.text-field :id="'gross-'.$type->id" :name="'lines['.$type->id.'][gross_amount]'" label="Realisasi bruto (Rp)" mono inputmode="decimal" autocomplete="off" class="text-right" :disabled="$disabled" :value="$money($lines[$type->id]->gross_amount ?? null) ?? '0,00'" />
                                <x-pm.text-field :id="'refund-'.$type->id" :name="'lines['.$type->id.'][refund_amount]'" label="Pengembalian belanja (Rp)" mono inputmode="decimal" autocomplete="off" class="text-right" :disabled="$disabled" :value="$money($lines[$type->id]->refund_amount ?? null) ?? '0,00'" />
                            </fieldset>
                        @endforeach
                    </div>
                @endif
            </x-pm.card>

            <x-pm.card title="Penyetoran, pajak, dan saldo BKU">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-pm.text-field name="treasury_deposit" label="Penyetoran ke kas negara/daerah (Rp)" mono inputmode="decimal" autocomplete="off" class="text-right" :disabled="$disabled" :value="$money($report->treasury_deposit) ?? '0,00'" hint="Mengurangi saldo dana, bukan realisasi belanja." />
                    <span class="hidden sm:block"></span>
                    <x-pm.text-field name="tax_withheld" label="Pajak dipungut (Rp)" mono inputmode="decimal" autocomplete="off" class="text-right" :disabled="$disabled" :value="$money($report->tax_withheld) ?? '0,00'" />
                    <x-pm.text-field name="tax_remitted" label="Pajak disetor (Rp)" mono inputmode="decimal" autocomplete="off" class="text-right" :disabled="$disabled" :value="$money($report->tax_remitted) ?? '0,00'" />
                    <x-pm.text-field name="bank_balance_bku" label="Saldo bank menurut BKU (Rp)" mono inputmode="decimal" autocomplete="off" class="text-right" :disabled="$disabled" :value="$money($report->bank_balance_bku)" />
                    <x-pm.text-field name="cash_balance_bku" label="Saldo tunai menurut BKU (Rp)" mono inputmode="decimal" autocomplete="off" class="text-right" :disabled="$disabled" :value="$money($report->cash_balance_bku)" />
                </div>
                <div class="mt-4">
                    <x-pm.text-field name="difference_note" label="Catatan selisih saldo" optional :disabled="$disabled" :value="$report->difference_note" hint="Wajib diisi saat mengajukan bila selisih saldo tidak nol." />
                </div>
            </x-pm.card>

            @if ($canEdit)
                <div class="flex gap-2">
                    <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan draf</x-pm.button>
                    <x-pm.link-button variant="secondary" :href="route('school.reports.index', ['tahun' => $report->fiscalYear->year])">Kembali</x-pm.link-button>
                </div>
            @else
                <div><x-pm.link-button variant="secondary" :href="route('school.reports.index', ['tahun' => $report->fiscalYear->year])">Kembali</x-pm.link-button></div>
            @endif
        </form>

        <aside class="grid gap-5" aria-label="Ringkasan saldo">
            <x-pm.card title="Ringkasan saldo" subtitle="Dihitung server saat laporan disimpan.">
                <x-pm.description-list :items="[
                    ['label' => 'Saldo akhir dana', 'value' => 'Rp '.$money($balance['ending_fund']), 'mono' => true],
                    ['label' => 'Saldo akhir pajak', 'value' => 'Rp '.$money($balance['ending_tax']), 'mono' => true],
                    ['label' => 'Saldo kas hitung (dana + pajak)', 'value' => 'Rp '.$money($balance['cash_calculated']), 'mono' => true],
                    ['label' => 'Saldo kas BKU (bank + tunai)', 'value' => $balance['cash_bku'] === null ? 'Belum diisi' : 'Rp '.$money($balance['cash_bku']), 'mono' => true],
                ]" />
                <div class="mt-4">
                    @if ($balance['difference'] === null)
                        <x-pm.badge tone="neutral" icon="info">Selisih saldo belum dapat dihitung</x-pm.badge>
                    @elseif ($hasDifference)
                        <x-pm.badge tone="warning" icon="alert">Selisih saldo Rp {{ $money($balance['difference']) }}</x-pm.badge>
                    @else
                        <x-pm.badge tone="success" icon="check-circle">Selisih saldo Rp 0,00</x-pm.badge>
                    @endif
                </div>
            </x-pm.card>

            <x-pm.card title="Sisa anggaran" subtitle="RKAS berlaku dikurangi realisasi neto kumulatif. Bukan saldo kas.">
                @if ($remaining['budget'] === null)
                    <p class="text-small text-ink-muted">RKAS belum diisi untuk sumber dana ini.</p>
                @else
                    <x-pm.description-list :items="[
                        ['label' => 'Anggaran RKAS berlaku', 'value' => 'Rp '.$money($remaining['budget']), 'mono' => true],
                        ['label' => 'Realisasi neto kumulatif', 'value' => 'Rp '.$money($remaining['realization']), 'mono' => true],
                        ['label' => 'Sisa anggaran', 'value' => 'Rp '.$money($remaining['remaining']), 'mono' => true],
                    ]" />
                @endif
            </x-pm.card>
        </aside>
    </div>

    @include('school.reports.review')

    @include('school.reports.documents')
@endsection
