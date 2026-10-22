@extends('layouts.app')

@section('title', $school->name)

@section('content')
    <x-pm.page-header :title="$school->name" :breadcrumb="['Sekolah', $school->name]">
        <x-slot:actions>
            <x-pm.badge :tone="$school->is_active ? 'success' : 'neutral'" :icon="$school->is_active ? 'check-circle' : 'x-circle'">{{ $school->is_active ? 'Aktif' : 'Nonaktif' }}</x-pm.badge>
            <x-pm.link-button variant="secondary" icon="edit" :href="route('admin.schools.edit', $school)">Ubah</x-pm.link-button>
            @if ($school->is_active)
                <x-pm.modal
                    id="deactivate-school"
                    title="Nonaktifkan sekolah?"
                    tone="danger"
                    trigger="Nonaktifkan"
                    confirm="Nonaktifkan sekolah"
                    :action="route('admin.schools.toggle-active', $school)"
                >Sekolah tidak dihapus; datanya tetap tersimpan dan dapat diaktifkan kembali.</x-pm.modal>
            @else
                <form method="POST" action="{{ route('admin.schools.toggle-active', $school) }}">
                    @csrf
                    <x-pm.button type="submit" variant="secondary">Aktifkan kembali</x-pm.button>
                </form>
            @endif
        </x-slot:actions>
    </x-pm.page-header>

    <div class="mt-5 grid gap-5">
        <x-pm.card title="Profil">
            <x-pm.description-list :columns="2" :items="[
                ['label' => 'NPSN', 'value' => $school->npsn, 'mono' => true],
                ['label' => 'Jenjang', 'value' => $school->level->label().' '.$school->status->label()],
                ['label' => 'Kecamatan', 'value' => $school->district->name],
                ['label' => 'Telepon', 'value' => $school->phone ?? '—'],
                ['label' => 'Alamat', 'value' => $school->address ?? '—'],
            ]" />
        </x-pm.card>

        <x-pm.card title="Pengelola" subtitle="Satu pengelola aktif per peran; pergantian mengakhiri pengelola sebelumnya." flush>
            @include('school.partials.officials', ['officials' => $officials, 'admin' => true])
            <div class="border-t border-line p-5">
                <h3 class="mb-3 text-h3 text-ink">Catat pergantian pengelola</h3>
                @include('school.partials.official-form', ['action' => route('admin.schools.officials.store', $school), 'submit' => 'Sahkan pengelola'])
            </div>
        </x-pm.card>

        <x-pm.card title="Akun sekolah" flush>
            @if ($accounts->isEmpty())
                <x-pm.empty-state icon="users" title="Belum ada akun" description="Buat akun operator atau kepala sekolah untuk sekolah ini.">
                    <x-slot:actions>
                        <x-pm.link-button :href="route('admin.users.create')" icon="plus">Tambah akun</x-pm.link-button>
                    </x-slot:actions>
                </x-pm.empty-state>
            @else
                <x-pm.data-table caption="Akun sekolah">
                    <x-slot:head>
                        <tr>
                            <th scope="col" class="px-5 py-3">Nama</th>
                            <th scope="col" class="px-5 py-3">Nama pengguna</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                        </tr>
                    </x-slot:head>
                    @foreach ($accounts as $account)
                        <tr>
                            <td class="px-5 py-3">{{ $account->name }}</td>
                            <td class="px-5 py-3 font-mono text-mono">{{ $account->username }}</td>
                            <td class="px-5 py-3"><x-pm.badge :tone="$account->is_active ? 'success' : 'neutral'">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</x-pm.badge></td>
                        </tr>
                    @endforeach
                </x-pm.data-table>
            @endif
        </x-pm.card>
    </div>
@endsection
