@props(['status'])
@php
    // Pemetaan docs/26 §Pemetaan status laporan ke badge.
    [$tone, $icon] = match ($status->value) {
        'diajukan' => ['info', 'send'],
        'dalam_pemeriksaan' => ['primary', 'eye'],
        'perlu_perbaikan' => ['warning', 'alert'],
        'selesai_diperiksa' => ['success', 'check-circle'],
        default => ['neutral', 'edit'],
    };
@endphp
<x-pm.badge :tone="$tone" :icon="$icon" {{ $attributes }}>{{ $status->label() }}</x-pm.badge>
