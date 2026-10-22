@extends('layouts.app')

@section('title', 'Sumber Dana')

@section('content')
    <x-pm.page-header title="Sumber Dana" description="Sumber dana BOS yang dilaporkan sekolah. Kode tidak dapat diubah; nonaktifkan bila tidak dipakai lagi.">
        <x-slot:actions>
            <x-pm.link-button :href="route('admin.fund-sources.create')" icon="plus">Tambah sumber dana</x-pm.link-button>
        </x-slot:actions>
    </x-pm.page-header>

    <x-pm.card flush class="mt-5">
        @if ($fundSources->isEmpty())
            <x-pm.empty-state icon="wallet" title="Belum ada sumber dana" description="Tambahkan sumber dana, misalnya BOS Reguler.">
                <x-slot:actions>
                    <x-pm.link-button :href="route('admin.fund-sources.create')" icon="plus">Tambah sumber dana</x-pm.link-button>
                </x-slot:actions>
            </x-pm.empty-state>
        @else
            <x-pm.data-table caption="Daftar sumber dana">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Kode</th>
                        <th scope="col" class="px-5 py-3">Nama</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($fundSources as $fundSource)
                    <tr>
                        <td class="px-5 py-3 font-mono text-mono">{{ $fundSource->code }}</td>
                        <td class="px-5 py-3">{{ $fundSource->name }}</td>
                        <td class="px-5 py-3">
                            @if ($fundSource->is_active)
                                <x-pm.badge tone="success" icon="check-circle">Aktif</x-pm.badge>
                            @else
                                <x-pm.badge tone="neutral" icon="x-circle">Nonaktif</x-pm.badge>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <x-pm.link-button variant="ghost" size="sm" :href="route('admin.fund-sources.edit', $fundSource)">Ubah</x-pm.link-button>
                        </td>
                    </tr>
                @endforeach
            </x-pm.data-table>
        @endif
    </x-pm.card>
@endsection
