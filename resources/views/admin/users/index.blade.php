@extends('layouts.app')

@section('title', 'Akun Pengguna')

@section('content')
    <x-pm.page-header title="Akun Pengguna" description="Akun dinas dan akun sekolah.">
        <x-slot:actions>
            <x-pm.link-button :href="route('admin.users.create')" icon="plus">Tambah akun</x-pm.link-button>
        </x-slot:actions>
    </x-pm.page-header>

    <x-pm.card flush class="mt-5">
        @if ($users->isEmpty())
            <x-pm.empty-state icon="users" title="Belum ada akun" description="Tambahkan akun pertama." />
        @else
            <x-pm.data-table caption="Daftar akun pengguna">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Nama</th>
                        <th scope="col" class="px-5 py-3">Nama pengguna</th>
                        <th scope="col" class="px-5 py-3">Peran</th>
                        <th scope="col" class="px-5 py-3">Sekolah</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($users as $account)
                    <tr>
                        <td class="px-5 py-3">{{ $account->name }}</td>
                        <td class="px-5 py-3 font-mono text-mono">{{ $account->username }}</td>
                        <td class="px-5 py-3">{{ \App\Enums\RoleName::tryFrom((string) $account->roles->first()?->name)?->label() ?? '—' }}</td>
                        <td class="px-5 py-3">{{ $account->school?->name ?? '—' }}</td>
                        <td class="px-5 py-3"><x-pm.badge :tone="$account->is_active ? 'success' : 'neutral'" :icon="$account->is_active ? 'check-circle' : 'x-circle'">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</x-pm.badge></td>
                        <td class="px-5 py-3 text-right">
                            <x-pm.link-button variant="ghost" size="sm" :href="route('admin.users.edit', $account)">Ubah</x-pm.link-button>
                        </td>
                    </tr>
                @endforeach
            </x-pm.data-table>
            <div class="border-t border-line px-5 py-3">{{ $users->links() }}</div>
        @endif
    </x-pm.card>
@endsection
