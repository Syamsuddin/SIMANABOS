@extends('layouts.app')

@section('title', 'Kecamatan')

@section('content')
    <x-pm.page-header title="Kecamatan" description="Wilayah tempat sekolah berada.">
        <x-slot:actions>
            <x-pm.link-button :href="route('admin.districts.create')" icon="plus">Tambah kecamatan</x-pm.link-button>
        </x-slot:actions>
    </x-pm.page-header>

    <x-pm.card flush class="mt-5">
        @if ($districts->isEmpty())
            <x-pm.empty-state icon="map-pin" title="Belum ada kecamatan" description="Tambahkan kecamatan sebelum mendaftarkan sekolah.">
                <x-slot:actions>
                    <x-pm.link-button :href="route('admin.districts.create')" icon="plus">Tambah kecamatan</x-pm.link-button>
                </x-slot:actions>
            </x-pm.empty-state>
        @else
            <x-pm.data-table caption="Daftar kecamatan">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-5 py-3">Kode</th>
                        <th scope="col" class="px-5 py-3">Nama</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($districts as $district)
                    <tr>
                        <td class="px-5 py-3 font-mono text-mono">{{ $district->code }}</td>
                        <td class="px-5 py-3">{{ $district->name }}</td>
                        <td class="px-5 py-3 text-right">
                            <x-pm.link-button variant="ghost" size="sm" :href="route('admin.districts.edit', $district)">Ubah</x-pm.link-button>
                        </td>
                    </tr>
                @endforeach
            </x-pm.data-table>
        @endif
    </x-pm.card>
@endsection
