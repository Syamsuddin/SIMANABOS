@props(['items', 'columns' => 1])
<dl {{ $attributes->class(['grid gap-x-6 gap-y-3', 'wide:grid-cols-2' => $columns === 2]) }}>
    @foreach ($items as $item)
        <div class="grid gap-0.5">
            <dt class="text-small text-ink-muted">{{ $item['label'] }}</dt>
            <dd @class(['text-body text-ink', 'font-mono text-mono' => $item['mono'] ?? false])>{{ $item['value'] }}</dd>
        </div>
    @endforeach
</dl>
