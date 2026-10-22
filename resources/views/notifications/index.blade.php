@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
    <x-pm.page-header title="Notifikasi" description="Pengingat tenggat dan penanda keterlambatan laporan." :breadcrumb="['Notifikasi']" />

    <x-pm.card class="mt-5">
        @if ($notifications->isEmpty())
            <x-pm.empty-state icon="bell" title="Belum ada notifikasi" description="Pengingat tenggat akan muncul di sini." />
        @else
            <ul class="divide-y divide-line">
                @foreach ($notifications as $notification)
                    <li class="flex items-start justify-between gap-4 py-3">
                        <p class="text-body {{ $notification->read_at ? 'text-ink-muted' : 'font-semibold text-ink' }}">{{ $notification->data['message'] ?? '' }}</p>
                        <span class="shrink-0 text-caption text-ink-muted">{{ $notification->created_at->translatedFormat('d M Y H:i') }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4">{{ $notifications->links() }}</div>
            @if (auth()->user()->unreadNotifications()->exists())
                <x-slot:footer>
                    <form method="POST" action="{{ route('notifications.read-all') }}" x-data="{ busy: false }" @submit="busy = true">
                        @csrf
                        <x-pm.button type="submit" variant="secondary" x-bind:disabled="busy">Tandai semua sudah dibaca</x-pm.button>
                    </form>
                </x-slot:footer>
            @endif
        @endif
    </x-pm.card>
@endsection
