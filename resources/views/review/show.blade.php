@extends('layouts.app')

@php
    $money = fn ($v) => $v === null ? 'Belum diisi' : 'Rp '.\App\Support\Money::format($v);
    $monthName = \Illuminate\Support\Carbon::create(null, $report->month, 1)->locale('id')->translatedFormat('F');
    $wita = fn ($t) => $t?->timezone('Asia/Makassar')->translatedFormat('d F Y, H.i').' WITA';
    $hasDifference = $balance['difference'] !== null && ! \Brick\Math\BigDecimal::of($balance['difference'])->isZero();
@endphp

@section('title', 'Pemeriksaan '.$monthName)

@section('content')
    <x-pm.page-header :title="'Pemeriksaan Laporan '.$monthName.' '.$report->fiscalYear->year" :description="$report->school->name.' · '.$report->fundSource->name" :breadcrumb="['Pemeriksaan', 'Antrean', $monthName]">
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                <x-pm.status-badge :status="$report->status" />
                @if ($report->needs_rereview)
                    <x-pm.badge tone="accent" icon="alert">Perlu tinjau ulang</x-pm.badge>
                @endif
            </div>
        </x-slot:actions>
    </x-pm.page-header>

    @if ($errors->domain->any())
        <x-pm.alert tone="danger" class="mt-5" title="Tindakan belum dapat dilakukan">
            <ul class="list-disc pl-5">@foreach ($errors->domain->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </x-pm.alert>
    @endif

    @if ($report->status === \App\Enums\ReportStatus::SelesaiDiperiksa)
        <x-pm.alert tone="info" class="mt-5" title="Laporan terkunci">Pemeriksaan administratif: angka agregat cocok dengan PDF dan dokumen wajib lengkap. Status ini bukan pernyataan keabsahan setiap transaksi.</x-pm.alert>
    @endif

    <div class="mt-5 flex flex-wrap gap-2">
        @if ($canClaim)
            <x-pm.modal id="claim" title="Ambil laporan untuk diperiksa?" :action="route('review.reports.claim', $report)" trigger="Ambil untuk diperiksa" triggerVariant="primary" confirm="Ambil">
                Laporan akan tercatat atas nama Anda dan tidak dapat diambil pemeriksa lain.
            </x-pm.modal>
        @endif
        @if ($canReview)
            <x-pm.modal id="release" title="Lepas laporan?" :action="route('review.reports.release', $report)" trigger="Lepas ke antrean" confirm="Lepas">
                Laporan kembali ke antrean tanpa keputusan.
            </x-pm.modal>
            <x-pm.modal id="revise" title="Minta perbaikan?" :action="route('review.reports.revise', $report)" trigger="Minta perbaikan" tone="danger" confirm="Minta perbaikan">
                Laporan dikembalikan ke sekolah dan angkanya dapat diubah kembali. Pastikan setiap butir sudah dicatat.
            </x-pm.modal>
            <x-pm.modal id="complete" title="Nyatakan selesai diperiksa?" :action="route('review.reports.complete', $report)" trigger="Nyatakan selesai diperiksa" tone="danger" confirm="Nyatakan selesai">
                Laporan terkunci dan tidak dapat diubah sekolah. Hanya admin dinas yang dapat membuka kunci.
            </x-pm.modal>
        @endif
        @if ($canUnlock)
            <x-pm.modal id="unlock" title="Buka kunci laporan?" :action="route('review.reports.unlock', $report)" trigger="Buka kunci" tone="danger" confirm="Buka kunci">
                Laporan kembali ke status perlu perbaikan dan angkanya dapat diubah sekolah. Saldo bulan-bulan sesudahnya dihitung ulang setelah laporan diajukan kembali; yang sudah selesai ditandai tinjau ulang.
                <x-slot:fields>
                    <label for="unlock-reason" class="block text-label text-ink">Alasan buka kunci</label>
                    <textarea id="unlock-reason" name="reason" rows="3" maxlength="500" required class="mt-1 w-full rounded-md border border-line bg-surface p-2 text-body text-ink"></textarea>
                </x-slot:fields>
            </x-pm.modal>
        @endif
    </div>

    <div class="mt-5 grid items-start gap-5 wide:grid-cols-[2fr_1fr]">
        <div class="grid gap-5">
            <x-pm.card title="Angka laporan">
                <x-pm.description-list :items="[
                    ['label' => 'Saldo awal dana', 'value' => $money($report->opening_fund), 'mono' => true],
                    ['label' => 'Saldo awal pajak', 'value' => $money($report->opening_tax), 'mono' => true],
                    ['label' => 'Penerimaan dana BOS', 'value' => $money($report->receipt_bos), 'mono' => true],
                    ['label' => 'Penerimaan lain', 'value' => $money($report->receipt_other).($report->receipt_other_note ? ' ('.$report->receipt_other_note.')' : ''), 'mono' => true],
                    ['label' => 'Penyetoran', 'value' => $money($report->treasury_deposit), 'mono' => true],
                    ['label' => 'Pajak dipungut', 'value' => $money($report->tax_withheld), 'mono' => true],
                    ['label' => 'Pajak disetor', 'value' => $money($report->tax_remitted), 'mono' => true],
                    ['label' => 'Saldo bank BKU', 'value' => $money($report->bank_balance_bku), 'mono' => true],
                    ['label' => 'Saldo tunai BKU', 'value' => $money($report->cash_balance_bku), 'mono' => true],
                ]" />
            </x-pm.card>

            <x-pm.card flush title="Realisasi per jenis belanja">
                @if ($report->lines->isEmpty())
                    <x-pm.empty-state icon="wallet" title="Tidak ada realisasi" description="Sekolah tidak mengisi realisasi belanja bulan ini." />
                @else
                    <x-pm.data-table caption="Realisasi per jenis belanja">
                        <x-slot:head>
                            <tr>
                                <th scope="col" class="px-5 py-3">Jenis belanja</th>
                                <th scope="col" class="px-5 py-3 text-right">Bruto (Rp)</th>
                                <th scope="col" class="px-5 py-3 text-right">Pengembalian (Rp)</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($report->lines as $line)
                            <tr>
                                <td class="px-5 py-3">{{ $line->expenseType->name }}</td>
                                <td class="px-5 py-3 text-right font-mono text-mono">{{ \App\Support\Money::format($line->gross_amount) }}</td>
                                <td class="px-5 py-3 text-right font-mono text-mono">{{ \App\Support\Money::format($line->refund_amount) }}</td>
                            </tr>
                        @endforeach
                    </x-pm.data-table>
                @endif
            </x-pm.card>

            <x-pm.card title="Rekonsiliasi saldo">
                <x-pm.description-list :items="[
                    ['label' => 'Saldo akhir dana', 'value' => $money($balance['ending_fund']), 'mono' => true],
                    ['label' => 'Saldo akhir pajak', 'value' => $money($balance['ending_tax']), 'mono' => true],
                    ['label' => 'Saldo kas hitung (dana + pajak)', 'value' => $money($balance['cash_calculated']), 'mono' => true],
                    ['label' => 'Saldo kas BKU (bank + tunai)', 'value' => $money($balance['cash_bku']), 'mono' => true],
                ]" />
                <div class="mt-4">
                    @if ($hasDifference)
                        <x-pm.badge tone="warning" icon="alert">Selisih saldo {{ $money($balance['difference']) }}</x-pm.badge>
                        @if ($report->difference_note)<p class="mt-2 text-small text-ink-muted">Catatan sekolah: {{ $report->difference_note }}</p>@endif
                    @else
                        <x-pm.badge tone="success" icon="check-circle">Selisih saldo Rp 0,00</x-pm.badge>
                    @endif
                </div>
            </x-pm.card>

            <x-pm.card title="Catatan pemeriksaan" subtitle="Catatan menunjuk butir tertentu agar sekolah tahu yang harus diperbaiki.">
                @forelse ($report->reviewNotes as $note)
                    <div class="border-b border-line py-3 last:border-b-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-pm.badge tone="neutral">{{ $note->target_type === 'general' ? 'Umum' : ($note->target_type === 'field' ? 'Isian: ' : 'Dokumen: ').$note->target_key }}</x-pm.badge>
                            @if ($note->resolved_at)
                                <x-pm.badge tone="success" icon="check-circle">Selesai</x-pm.badge>
                            @elseif ($note->responded_at)
                                <x-pm.badge tone="info" icon="send">Ditanggapi</x-pm.badge>
                            @else
                                <x-pm.badge tone="warning" icon="alert">Belum ditanggapi</x-pm.badge>
                            @endif
                        </div>
                        <p class="mt-2 text-body text-ink">{{ $note->body }}</p>
                        <p class="text-small text-ink-muted">{{ $note->reviewer->name }} · {{ $wita($note->created_at) }}</p>
                        @if ($note->response_body)
                            <p class="mt-2 rounded-md bg-surface-sunken px-3 py-2 text-body text-ink">Tanggapan sekolah: {{ $note->response_body }}</p>
                        @endif
                        @if ($canReview && $note->isOpen())
                            <x-pm.modal :id="'resolve-'.$note->id" title="Tandai catatan selesai?" :action="route('review.reports.notes.resolve', [$report, $note])" trigger="Tandai selesai" triggerSize="sm" confirm="Tandai selesai">
                                Catatan dianggap sudah dipenuhi sekolah.
                            </x-pm.modal>
                        @endif
                    </div>
                @empty
                    <x-pm.empty-state icon="file-text" title="Belum ada catatan" description="Tambahkan catatan bila ada butir yang perlu diperbaiki sekolah." />
                @endforelse

                @if ($canReview)
                    <form method="POST" action="{{ route('review.reports.notes.store', $report) }}" class="mt-4 grid gap-4 border-t border-line pt-4" x-data="{ busy: false }" @submit="busy = true" novalidate>
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-pm.select name="target_type" label="Butir" :options="['general' => 'Umum', 'field' => 'Isian angka', 'document_type' => 'Jenis dokumen']" :value="old('target_type', 'general')" required />
                            <x-pm.text-field name="target_key" label="Nama isian atau kode dokumen" optional :value="old('target_key')" maxlength="50" />
                        </div>
                        <x-pm.text-field name="body" label="Catatan" multiline required :value="old('body')" />
                        <div><x-pm.button type="submit" variant="secondary" x-bind:disabled="busy" x-bind:aria-busy="busy">Tambah catatan</x-pm.button></div>
                    </form>
                @endif
            </x-pm.card>
        </div>

        <aside class="grid gap-5" aria-label="Dokumen dan riwayat">
            <x-pm.card title="Dokumen PDF">
                @forelse ($documents as $document)
                    <div class="flex items-center justify-between gap-3 border-b border-line py-2 last:border-b-0">
                        <div class="min-w-0">
                            <p class="truncate text-body text-ink">{{ $document->currentVersion?->original_name }}</p>
                            <p class="text-small text-ink-muted">{{ $document->types->pluck('name')->join(', ') ?: 'Belum ditandai' }}</p>
                        </div>
                        @if ($document->currentVersion)
                            <x-pm.link-button variant="secondary" size="sm" icon="download" :href="route('documents.download', $document)">Unduh</x-pm.link-button>
                        @endif
                    </div>
                @empty
                    <x-pm.empty-state icon="file-text" title="Belum ada dokumen" description="Sekolah belum mengunggah PDF." />
                @endforelse
            </x-pm.card>

            <x-pm.card title="Kelengkapan dokumen wajib">
                @if ($completeness['required']->isEmpty())
                    <p class="text-small text-ink-muted">Belum ada aturan dokumen wajib.</p>
                @elseif ($completeness['complete'])
                    <x-pm.badge tone="success" icon="check-circle">Semua dokumen wajib lengkap</x-pm.badge>
                @else
                    <x-pm.badge tone="warning" icon="alert">{{ $completeness['missing']->count() }} dokumen wajib belum ada</x-pm.badge>
                    <ul class="mt-2 list-disc pl-5 text-small text-ink">@foreach ($completeness['missing'] as $type)<li>{{ $type->name }}</li>@endforeach</ul>
                @endif
            </x-pm.card>

            <x-pm.card title="Riwayat status">
                @forelse ($report->statusHistories as $history)
                    <div class="border-b border-line py-2 text-small last:border-b-0">
                        <p class="text-ink">{{ $history->from_status->label() }} → {{ $history->to_status->label() }}</p>
                        <p class="text-ink-muted">{{ $history->actor->name }} · {{ $wita($history->created_at) }}</p>
                        @if ($history->note)<p class="text-ink-muted">{{ $history->note }}</p>@endif
                    </div>
                @empty
                    <p class="text-small text-ink-muted">Belum ada perubahan status.</p>
                @endforelse
            </x-pm.card>
        </aside>
    </div>
@endsection
