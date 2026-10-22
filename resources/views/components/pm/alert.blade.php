@props(['tone' => 'info', 'title' => null])
@php
    $tones = [
        'info' => ['box' => 'border-info bg-info-soft', 'accent' => 'text-info', 'icon' => 'info'],
        'success' => ['box' => 'border-success bg-success-soft', 'accent' => 'text-success', 'icon' => 'check-circle'],
        'warning' => ['box' => 'border-warning bg-warning-soft', 'accent' => 'text-warning', 'icon' => 'alert'],
        'danger' => ['box' => 'border-danger bg-danger-soft', 'accent' => 'text-danger', 'icon' => 'x-circle'],
    ];
    $t = $tones[$tone];
@endphp
<div
    role="{{ in_array($tone, ['warning', 'danger'], true) ? 'alert' : 'status' }}"
    {{ $attributes->class(['flex items-start gap-3 rounded-md border px-4 py-3 text-body text-ink', $t['box']]) }}
>
    <x-pm.icon :name="$t['icon']" :size="20" class="mt-0.5 {{ $t['accent'] }}" />
    <div class="grid min-w-0 flex-1 gap-0.5">
        @if ($title)<strong class="font-bold {{ $t['accent'] }}">{{ $title }}</strong>@endif
        <div>{{ $slot }}</div>
    </div>
</div>
