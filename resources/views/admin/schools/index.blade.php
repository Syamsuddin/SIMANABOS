@extends('layouts.app')

@section('title', 'Sekolah')

@section('content')
    <x-pm.page-header title="Sekolah" description="Daftar SD dan SMP yang diawasi.">
        <x-slot:actions>
            <x-pm.link-button :href="route('admin.schools.create')" icon="plus">Tambah sekolah</x-pm.link-button>
        </x-slot:actions>
    </x-pm.page-header>

    <form method="GET" action="{{ route('admin.schools.index') }}" class="mt-5 flex max-w-md items-end gap-2" role="search">
        <div class="min-w-0 flex-1">
            <x-pm.text-field name="q" label="Cari sekolah" :value="$term" placeholder="Nama atau NPSN" icon="search" />
        </div>
        <x-pm.button type="submit" variant="secondary">Cari</x-pm.button>
    </form>

    <x-pm.card flush class="mt-5">
        @if ($schools->isEmpty())
            <x-pm.empty-state
                icon="building"
                :title="$term ? 'Sekolah tidak ditemukan' : 'Belum ada sekolah'"
                :description="$term ? 'Coba kata kunci lain.' : 'Tambahkan sekolah agar akun operator dan laporan dapat dibuat.'"
            >
                @unless ($term)
                    <x-slot:actions>
                        <x-pm.link-button :href="route('admin.schools.create')" icon="plus">Tambah sekolah</x-pm.link-button>
                    </x-slot:actions>
                @endunless
            </x-pm.empty-state>
        @else
            <x-pm.data-table caption="Daftar sekolah">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">NPSN</th>
                        <th scope="col" class="px-5 py-3">Nama</th>
                        <th scope="col" class="px-5 py-3">Jenjang</th>
                        <th scope="col" class="px-5 py-3">Kecamatan</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($schools as $school)
                    <tr>
                        <td class="px-5 py-3 font-mono text-mono">{{ $school->npsn }}</td>
                        <td class="px-5 py-3">{{ $school->name }}</td>
                        <td class="px-5 py-3">{{ $school->level->label() }} {{ $school->status->label() }}</td>
                        <td class="px-5 py-3">{{ $school->district->name }}</td>
                        <td class="px-5 py-3">
                            <x-pm.badge :tone="$school->is_active ? 'success' : 'neutral'" :icon="$school->is_active ? 'check-circle' : 'x-circle'">{{ $school->is_active ? 'Aktif' : 'Nonaktif' }}</x-pm.badge>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <x-pm.link-button variant="ghost" size="sm" :href="route('admin.schools.show', $school)">Lihat</x-pm.link-button>
                        </td>
                    </tr>
                @endforeach
            </x-pm.data-table>
            <div class="border-t border-line px-5 py-3">{{ $schools->links() }}</div>
        @endif
    </x-pm.card>
@endsection
