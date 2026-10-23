{{-- Pengajuan, persetujuan kepala sekolah, catatan pemeriksa, dan riwayat status (F-07). Diisi dari ReportController@edit. --}}
@php
    $wita = fn ($t) => $t?->timezone('Asia/Makassar')->translatedFormat('d F Y, H.i').' WITA';
    $isOperator = auth()->user()->hasRole(\App\Enums\RoleName::OperatorSekolah->value);
@endphp

@if ($errors->domain->any())
    <x-pm.alert tone="danger" class="mt-5" title="Laporan belum dapat diproses">
        <ul class="list-disc pl-5">@foreach ($errors->domain->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </x-pm.alert>
@endif

@if ($report->status === \App\Enums\ReportStatus::SelesaiDiperiksa)
    <x-pm.alert tone="info" class="mt-5" title="Laporan selesai diperiksa">Pemeriksaan administratif: angka agregat cocok dengan PDF dan dokumen wajib lengkap. Status ini bukan pernyataan keabsahan setiap transaksi.</x-pm.alert>
@endif

<div class="mt-5 grid gap-5">
    @if ($canSubmit || $canWithdraw || $canApprove || $principalRequired)
        <x-pm.card title="Pengajuan" subtitle="Laporan yang diajukan tidak dapat diubah sampai pemeriksa memintanya diperbaiki.">
            @if ($principalRequired)
                <p class="mb-3 text-small text-ink-muted">
                    @if ($report->principal_approved_at)
                        <x-pm.badge tone="success" icon="check-circle">Disetujui kepala sekolah</x-pm.badge> {{ $wita($report->principal_approved_at) }}
                    @else
                        <x-pm.badge tone="warning" icon="alert">Menunggu persetujuan kepala sekolah</x-pm.badge> Perubahan angka membatalkan persetujuan.
                    @endif
                </p>
            @endif
            <div class="flex flex-wrap gap-2">
                @if ($canApprove)
                    <x-pm.modal id="approve" title="Setujui laporan?" :action="route('school.reports.approve', $report)" trigger="Setujui laporan" confirm="Setujui">
                        Anda menyetujui angka laporan ini untuk diajukan. Perubahan angka sesudahnya membatalkan persetujuan.
                    </x-pm.modal>
                @endif
                @if ($canSubmit)
                    <x-pm.modal id="submit" title="Ajukan laporan?" :action="route('school.reports.submit', $report)" trigger="Ajukan" triggerVariant="primary" confirm="Ajukan">
                        Setelah diajukan, laporan diperiksa dinas dan angkanya tidak dapat diubah. Anda masih dapat menariknya selama belum diambil pemeriksa.
                    </x-pm.modal>
                @endif
                @if ($canWithdraw)
                    <x-pm.modal id="withdraw" title="Tarik kembali laporan?" :action="route('school.reports.withdraw', $report)" trigger="Tarik kembali" confirm="Tarik kembali">
                        Laporan kembali menjadi draf dan dapat diubah.
                    </x-pm.modal>
                @endif
            </div>
        </x-pm.card>
    @endif

    @if ($report->reviewNotes->isNotEmpty())
        <x-pm.card title="Catatan pemeriksa" subtitle="Tanggapi setiap catatan sebelum mengajukan ulang.">
            @foreach ($report->reviewNotes as $note)
                <div class="border-b border-line py-3 last:border-b-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-pm.badge tone="neutral">{{ $note->target_type === 'general' ? 'Umum' : ($note->target_type === 'field' ? 'Isian: ' : 'Dokumen: ').$note->target_key }}</x-pm.badge>
                        @if ($note->resolved_at)
                            <x-pm.badge tone="success" icon="check-circle">Selesai</x-pm.badge>
                        @elseif ($note->responded_at)
                            <x-pm.badge tone="info" icon="send">Sudah ditanggapi</x-pm.badge>
                        @else
                            <x-pm.badge tone="warning" icon="alert">Perlu ditanggapi</x-pm.badge>
                        @endif
                    </div>
                    <p class="mt-2 text-body text-ink">{{ $note->body }}</p>
                    <p class="text-small text-ink-muted">{{ $note->reviewer->name }} · {{ $wita($note->created_at) }}</p>
                    @if ($note->response_body)
                        <p class="mt-2 rounded-md bg-surface-sunken px-3 py-2 text-body text-ink">Tanggapan Anda: {{ $note->response_body }}</p>
                    @endif
                    @if ($canRespond && $note->isOpen())
                        <form method="POST" action="{{ route('school.reports.notes.respond', [$report, $note]) }}" class="mt-3 grid gap-3" x-data="{ busy: false }" @submit="busy = true" novalidate>
                            @csrf @method('PUT')
                            <x-pm.text-field :id="'response-'.$note->id" name="response_body" label="Tanggapan" multiline required :value="$note->response_body" />
                            <div><x-pm.button type="submit" variant="secondary" size="sm" x-bind:disabled="busy" x-bind:aria-busy="busy">Simpan tanggapan</x-pm.button></div>
                        </form>
                    @endif
                </div>
            @endforeach
        </x-pm.card>
    @endif

    <x-pm.card title="Riwayat status">
        @forelse ($report->statusHistories as $history)
            <div class="border-b border-line py-2 text-small last:border-b-0">
                <p class="text-ink">{{ $history->from_status->label() }} → {{ $history->to_status->label() }}</p>
                <p class="text-ink-muted">{{ $history->actor->name }} · {{ $wita($history->created_at) }}</p>
                @if ($history->note)<p class="text-ink-muted">{{ $history->note }}</p>@endif
            </div>
        @empty
            <p class="text-small text-ink-muted">Laporan belum pernah diajukan.</p>
        @endforelse
    </x-pm.card>

    @if ($auditTrail !== null)
        @include('reports.partials.audit-trail')
    @endif
</div>
