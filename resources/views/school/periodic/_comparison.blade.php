{{-- Selisih angka laporan periodik terhadap Σ realisasi neto bulanan pada rentangnya (F-09). Hanya pembanding. --}}
@if ($comparison === null)
    @if ($report?->kind === \App\Enums\PeriodicKind::Stage || $kind === \App\Enums\PeriodicKind::Stage)
        <p class="text-small text-ink-muted">Rentang bulan tahap ini belum ditetapkan Dinas.</p>
    @endif
@else
    @php $months = fn (int $m) => \Illuminate\Support\Carbon::create(null, $m, 1)->locale('id')->translatedFormat('F'); @endphp
    <dl class="grid gap-1 text-small">
        <div class="flex justify-between gap-3"><dt class="text-ink-muted">Σ realisasi neto {{ $months($comparison['from']) }}–{{ $months($comparison['to']) }}</dt><dd class="font-mono text-mono">{{ \App\Support\Money::format($comparison['monthly_total']) }}</dd></div>
        <div class="flex justify-between gap-3"><dt class="text-ink-muted">Laporan bulanan dihitung</dt><dd>{{ $comparison['counted'] }} dari {{ $comparison['months'] }} bulan (selain draf)</dd></div>
        <div class="flex justify-between gap-3"><dt class="text-ink-muted">Dilaporkan</dt><dd class="font-mono text-mono">{{ $comparison['reported'] !== null ? \App\Support\Money::format($comparison['reported']) : '—' }}</dd></div>
    </dl>
    @if ($comparison['difference'] === null)
        <x-pm.badge tone="info" icon="info">Isi total realisasi untuk melihat selisih</x-pm.badge>
    @elseif (\Brick\Math\BigDecimal::of($comparison['difference'])->isZero())
        <x-pm.badge tone="success" icon="check-circle">Sesuai: selisih 0,00</x-pm.badge>
    @else
        <x-pm.badge tone="warning" icon="alert">Selisih {{ \App\Support\Money::format($comparison['difference']) }}</x-pm.badge>
    @endif
@endif
