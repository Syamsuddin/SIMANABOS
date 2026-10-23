{{-- Jejak audit laporan (F-14, docs/15). Hanya dirender bila $auditTrail tidak null (policy viewAudit). --}}
<x-pm.card title="Jejak audit" subtitle="Setiap perubahan angka, dokumen, dan status tercatat dan tidak dapat diubah.">
    @forelse ($auditTrail as $entry)
        <div class="border-b border-line py-2 text-small last:border-b-0">
            <p class="text-ink">{{ $entry['subject'] }} · {{ $entry['event'] }}</p>
            <p class="text-ink-muted">{{ $entry['actor'] }} · {{ $entry['at']?->timezone('Asia/Makassar')->translatedFormat('d F Y, H.i') }} WITA</p>
            @if ($entry['changes'])
                <ul class="mt-1 grid gap-1 text-ink-muted">
                    @foreach ($entry['changes'] as $change)
                        <li><span class="text-ink">{{ $change['field'] }}:</span>
                            @if ($change['old'] !== null)<span class="line-through">{{ $change['old'] }}</span> →@endif
                            {{ $change['new'] ?? '—' }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @empty
        <x-pm.empty-state icon="file-text" title="Belum ada jejak audit" description="Perubahan pada laporan ini akan tercatat di sini." />
    @endforelse
</x-pm.card>
