@props(['caption'])
<div class="overflow-x-auto">
    <table class="w-full border-collapse text-left text-body">
        <caption class="sr-only">{{ $caption }}</caption>
        <thead class="bg-surface-sunken text-caption text-ink-muted">
            {{ $head }}
        </thead>
        <tbody class="divide-y divide-line text-ink">
            {{ $slot }}
        </tbody>
    </table>
</div>
