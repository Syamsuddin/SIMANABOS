@extends('layouts.app')

@section('title', 'Laporan Periodik')

@section('content')
    <x-pm.page-header title="Laporan Periodik" description="Angka laporan Tahap dan Tahunan dari ARKAS dibandingkan dengan jumlah laporan bulanan. Angka ini hanya pembanding dan tidak mengubah realisasi bulanan." :breadcrumb="['Data sekolah', 'Laporan periodik']" />

    @if ($errors->any())
        <x-pm.alert tone="danger" class="mt-5" title="Laporan periodik belum tersimpan">{{ $errors->first() }}</x-pm.alert>
    @endif

    <x-pm.card class="mt-5">
        <form method="GET" action="{{ route('school.periodic.index') }}" class="grid items-end gap-4 sm:grid-cols-[1fr_auto]">
            <x-pm.select name="tahun" label="Tahun anggaran" :options="$fiscalYears->pluck('year', 'year')->all()" :value="$fiscalYear?->year" placeholder="Pilih tahun" />
            <x-pm.button type="submit" variant="secondary">Tampilkan</x-pm.button>
        </form>
    </x-pm.card>

    @if (! $fiscalYear)
        <x-pm.card flush class="mt-5"><x-pm.empty-state icon="calendar" title="Belum ada tahun anggaran" description="Dinas belum membuka tahun anggaran." /></x-pm.card>
    @elseif ($slots->isEmpty())
        <x-pm.card flush class="mt-5"><x-pm.empty-state icon="wallet" title="Pagu belum ditetapkan" description="Laporan periodik dapat diisi setelah Dinas menetapkan pagu sekolah Anda untuk tahun {{ $fiscalYear->year }}." /></x-pm.card>
    @else
        @foreach ($slots as $fundSlots)
            <h2 class="mt-8 text-h3 font-semibold text-ink">{{ $fundSlots->first()['fund']->name }}</h2>
            @foreach ($fundSlots as $slot)
                @php
                    $report = $slot['report'];
                    $kind = $slot['kind'];
                    $uid = $slot['fund']->id.'-'.$kind->value.'-'.$slot['stage_no'];
                    $comparison = $report ? $comparisons[$report->id] : null;
                @endphp
                <x-pm.card class="mt-4 max-w-2xl" :title="$slot['label']">
                    <form method="POST" action="{{ route('school.periodic.update') }}" novalidate class="grid gap-4" x-data="{ busy: false }" @submit="busy = true">
                        @csrf @method('PUT')
                        <input type="hidden" name="fiscal_year_id" value="{{ $fiscalYear->id }}">
                        <input type="hidden" name="fund_source_id" value="{{ $slot['fund']->id }}">
                        <input type="hidden" name="kind" value="{{ $kind->value }}">
                        <input type="hidden" name="stage_no" value="{{ $slot['stage_no'] }}">
                        @if ($kind->hasFigures())
                            <x-pm.text-field :id="'real-'.$uid" name="reported_total_realization" label="Total realisasi dilaporkan (Rp)" mono inputmode="decimal" class="text-right" :disabled="! $canEdit"
                                :value="$report?->reported_total_realization !== null ? \App\Support\Money::format($report->reported_total_realization) : null" />
                            <x-pm.text-field :id="'bal-'.$uid" name="reported_ending_balance" label="Saldo akhir dilaporkan (Rp)" mono inputmode="decimal" class="text-right" :disabled="! $canEdit"
                                :value="$report?->reported_ending_balance !== null ? \App\Support\Money::format($report->reported_ending_balance) : null" />
                        @endif
                        <x-pm.text-field :id="'note-'.$uid" name="note" label="Catatan" optional maxlength="1000" :disabled="! $canEdit" :value="$report?->note" />
                        @include('school.periodic._comparison', ['comparison' => $comparison, 'report' => $report, 'kind' => $kind])
                        @if ($canEdit)
                            <div><x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan</x-pm.button></div>
                        @endif
                    </form>

                    <div class="mt-5 border-t border-line pt-4">
                        <p class="text-small font-semibold text-ink">Dokumen PDF ({{ $documentTypes[$kind->documentTypeCode()]->name }})</p>
                        @if ($report === null)
                            <p class="mt-2 text-small text-ink-muted">Simpan terlebih dahulu untuk dapat mengunggah dokumen.</p>
                        @else
                            @forelse ($report->documents as $document)
                                @php $current = $document->currentVersion; @endphp
                                <div class="mt-3 grid gap-2 rounded-md border border-line p-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="min-w-0 break-words text-body text-ink">{{ $current->original_name }} <span class="text-small text-ink-muted">· versi {{ $current->version_no }}</span></p>
                                        <x-pm.link-button variant="secondary" size="sm" icon="download" :href="route('documents.download', $document)">Unduh</x-pm.link-button>
                                    </div>
                                    @if ($document->versions->count() > 1)
                                        <details class="text-small text-ink-muted">
                                            <summary class="cursor-pointer">Versi sebelumnya ({{ $document->versions->count() - 1 }})</summary>
                                            <ul class="mt-2 grid gap-1">
                                                @foreach ($document->versions->skip(1) as $old)
                                                    <li><a class="text-primary underline" href="{{ route('documents.download', [$document, $old->version_no]) }}">Versi {{ $old->version_no }}</a> · {{ $old->original_name }}</li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                    @if ($canEdit)
                                        <form method="POST" action="{{ route('school.periodic.documents.replace', [$report, $document]) }}" enctype="multipart/form-data" class="grid gap-3" x-data="{ busy: false }" @submit="busy = true">
                                            @csrf
                                            <x-pm.text-field :id="'replace-file-'.$document->id" name="file" type="file" accept="application/pdf,.pdf" label="Ganti berkas" :error="false" hint="Versi lama tetap tersimpan." />
                                            <x-pm.text-field :id="'replace-reason-'.$document->id" name="replaced_reason" label="Alasan penggantian" optional maxlength="255" />
                                            <div><x-pm.button type="submit" variant="secondary" size="sm" x-bind:disabled="busy" x-bind:aria-busy="busy">Ganti berkas</x-pm.button></div>
                                        </form>
                                        <div>
                                            <x-pm.modal :id="'delete-doc-'.$document->id" title="Hapus dokumen?" :action="route('school.periodic.documents.destroy', [$report, $document])" method="DELETE" tone="danger" trigger="Hapus" triggerSize="sm" confirm="Hapus dokumen">
                                                Dokumen "{{ $current->original_name }}" tidak akan dihitung lagi.
                                            </x-pm.modal>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="mt-2 text-small text-ink-muted">Belum ada dokumen.</p>
                            @endforelse

                            @if ($canEdit)
                                <form method="POST" action="{{ route('school.periodic.documents.store', $report) }}" enctype="multipart/form-data" class="mt-4 grid gap-3" x-data="{ busy: false }" @submit="busy = true">
                                    @csrf
                                    <x-pm.text-field :id="'new-file-'.$uid" name="file" type="file" accept="application/pdf,.pdf" label="Unggah berkas PDF" :error="false" />
                                    <div><x-pm.button type="submit" variant="primary" icon="upload" x-bind:disabled="busy" x-bind:aria-busy="busy">Unggah</x-pm.button></div>
                                </form>
                            @endif
                        @endif
                    </div>
                </x-pm.card>
            @endforeach
        @endforeach
    @endif
@endsection
