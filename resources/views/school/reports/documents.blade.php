{{-- Dokumen PDF laporan bulanan (F-06). Form terpisah dari form angka agar tidak bersarang. --}}
@php
    $canDelete = $canEdit && $report->submitted_at === null;
    $size = fn (int $bytes) => $bytes >= 1048576 ? number_format($bytes / 1048576, 1, ',', '.').' MB' : number_format(max($bytes, 1) / 1024, 0, ',', '.').' KB';
@endphp
<x-pm.card class="mt-5" title="Dokumen PDF" subtitle="Satu berkas boleh memuat beberapa jenis dokumen (PDF gabungan). Maksimal {{ number_format(config('simanabos.max_upload_kb') / 1024, 0, ',', '.') }} MB per berkas.">
    <div class="grid gap-3">
        <p class="text-small font-semibold text-ink">Kelengkapan dokumen wajib</p>
        @if ($completeness['required']->isEmpty())
            <p class="text-small text-ink-muted">Dinas belum menetapkan dokumen wajib untuk laporan ini.</p>
        @else
            <ul class="flex flex-wrap gap-2" aria-label="Kelengkapan dokumen wajib">
                @foreach ($completeness['required'] as $type)
                    @if ($completeness['missing']->contains('id', $type->id))
                        <li><x-pm.badge tone="danger" icon="x-circle">{{ $type->name }}: belum ada</x-pm.badge></li>
                    @else
                        <li><x-pm.badge tone="success" icon="check-circle">{{ $type->name }}: lengkap</x-pm.badge></li>
                    @endif
                @endforeach
            </ul>
        @endif
    </div>

    <div class="mt-5">
        @if ($documents->isEmpty())
            <x-pm.empty-state icon="file-text" title="Belum ada dokumen" description="Unggah PDF hasil ARKAS lalu tandai jenis dokumen yang dimuatnya." />
        @else
            <ul class="divide-y divide-line rounded-md border border-line">
                @foreach ($documents as $document)
                    @php $current = $document->currentVersion; @endphp
                    <li class="grid gap-3 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="break-words text-body font-semibold text-ink">{{ $current->original_name }}</p>
                                <p class="text-small text-ink-muted">
                                    Versi {{ $current->version_no }} · <span class="font-mono text-mono">{{ $size($current->size_bytes) }}</span> · {{ $current->created_at->timezone('Asia/Makassar')->translatedFormat('d F Y H:i') }} WITA
                                </p>
                            </div>
                            <x-pm.link-button variant="secondary" size="sm" icon="download" :href="route('documents.download', $document)">Unduh</x-pm.link-button>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @foreach ($document->types as $type)
                                <x-pm.badge tone="primary">{{ $type->name }}</x-pm.badge>
                            @endforeach
                        </div>

                        @if ($document->versions->count() > 1)
                            <details class="text-small text-ink-muted">
                                <summary class="cursor-pointer">Versi sebelumnya ({{ $document->versions->count() - 1 }})</summary>
                                <ul class="mt-2 grid gap-1">
                                    @foreach ($document->versions->skip(1) as $old)
                                        <li>
                                            <a class="text-primary underline" href="{{ route('documents.download', [$document, $old->version_no]) }}">Versi {{ $old->version_no }}</a>
                                            · {{ $old->original_name }}@if ($old->replaced_reason) · {{ $old->replaced_reason }}@endif
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif

                        @if ($canEdit)
                            <div class="grid gap-4 border-t border-line pt-3 md:grid-cols-2">
                                <form method="POST" action="{{ route('school.reports.documents.tag', [$report, $document]) }}" class="grid gap-2" x-data="{ busy: false }" @submit="busy = true">
                                    @csrf @method('PUT')
                                    <fieldset class="grid gap-1">
                                        <legend class="text-small font-semibold text-ink">Jenis dokumen</legend>
                                        @foreach ($documentTypes as $type)
                                            <label class="flex min-h-control items-center gap-3 text-body text-ink">
                                                <input type="checkbox" name="type_ids[]" value="{{ $type->id }}" class="size-5 rounded-sm border-line-strong accent-primary focus:outline-2 focus:outline-offset-2 focus:outline-focus" @checked($document->types->contains('id', $type->id))>
                                                {{ $type->name }}
                                            </label>
                                        @endforeach
                                    </fieldset>
                                    <div><x-pm.button type="submit" variant="secondary" size="sm" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan jenis</x-pm.button></div>
                                </form>

                                <div class="grid content-start gap-3">
                                    <form method="POST" action="{{ route('school.reports.documents.replace', [$report, $document]) }}" enctype="multipart/form-data" class="grid gap-3" x-data="{ busy: false }" @submit="busy = true">
                                        @csrf
                                        <x-pm.text-field :id="'replace-file-'.$document->id" name="file" type="file" accept="application/pdf,.pdf" label="Ganti berkas" :error="false" hint="Versi lama tetap tersimpan." />
                                        <x-pm.text-field :id="'replace-reason-'.$document->id" name="replaced_reason" label="Alasan penggantian" optional maxlength="255" />
                                        <div><x-pm.button type="submit" variant="secondary" size="sm" x-bind:disabled="busy" x-bind:aria-busy="busy">Ganti berkas</x-pm.button></div>
                                    </form>
                                    @if ($canDelete)
                                        <div>
                                            <x-pm.modal :id="'delete-doc-'.$document->id" title="Hapus dokumen?" :action="route('school.reports.documents.destroy', [$report, $document])" method="DELETE" tone="danger" trigger="Hapus" triggerSize="sm" confirm="Hapus dokumen">
                                                Dokumen "{{ $current->original_name }}" tidak akan dihitung lagi. Hapus hanya dapat dilakukan sebelum laporan diajukan.
                                            </x-pm.modal>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if ($canEdit)
        <form method="POST" action="{{ route('school.reports.documents.store', $report) }}" enctype="multipart/form-data" class="mt-5 grid gap-4 border-t border-line pt-5" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <p class="text-small font-semibold text-ink">Unggah dokumen baru</p>
            <x-pm.text-field id="new-document-file" name="file" type="file" accept="application/pdf,.pdf" label="Berkas PDF" required :error="$errors->first('file')" />
            <fieldset class="grid gap-1">
                <legend class="text-small font-semibold text-ink">Jenis dokumen yang dimuat <span class="text-danger" aria-hidden="true">*</span></legend>
                @foreach ($documentTypes as $type)
                    <label class="flex min-h-control items-center gap-3 text-body text-ink">
                        <input type="checkbox" name="type_ids[]" value="{{ $type->id }}" class="size-5 rounded-sm border-line-strong accent-primary focus:outline-2 focus:outline-offset-2 focus:outline-focus" @checked(in_array($type->id, array_map('intval', (array) old('type_ids', []))))>
                        {{ $type->name }}
                    </label>
                @endforeach
                @if ($errors->has('type_ids') || $errors->has('type_ids.*'))
                    <p class="flex items-center gap-1 text-small font-medium text-danger"><x-pm.icon name="alert" :size="16" />{{ $errors->first('type_ids') ?: $errors->first('type_ids.*') }}</p>
                @endif
            </fieldset>
            <div><x-pm.button type="submit" variant="primary" icon="upload" x-bind:disabled="busy" x-bind:aria-busy="busy">Unggah</x-pm.button></div>
        </form>
    @endif
</x-pm.card>
