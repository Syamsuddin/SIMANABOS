@extends('layouts.app')

@section('title', 'Profil Sekolah')

@section('content')
    <x-pm.page-header title="Profil Sekolah" :description="$school->name">
        <x-slot:actions>
            @can('updateProfile', $school)
                <x-pm.link-button variant="secondary" icon="edit" :href="route('school.profile.edit')">Ubah alamat dan telepon</x-pm.link-button>
            @endcan
        </x-slot:actions>
    </x-pm.page-header>

    <div class="mt-5 grid gap-5">
        <x-pm.card title="Identitas">
            <x-pm.description-list :items="[
                ['label' => 'NPSN', 'value' => $school->npsn, 'mono' => true],
                ['label' => 'Jenjang', 'value' => $school->level->label().' '.$school->status->label()],
                ['label' => 'Kecamatan', 'value' => $school->district->name],
                ['label' => 'Telepon', 'value' => $school->phone ?? '—'],
                ['label' => 'Alamat', 'value' => $school->address ?? '—'],
            ]" />
        </x-pm.card>

        <x-pm.card title="Pengelola" subtitle="Pergantian baru berlaku setelah disahkan Dinas." flush>
            @include('school.partials.officials', ['officials' => $officials])
            @can('proposeOfficial', $school)
                <div class="border-t border-line p-5">
                    <h3 class="mb-3 text-h3 text-ink">Usulkan pergantian pengelola</h3>
                    @include('school.partials.official-form', ['action' => route('school.officials.store'), 'submit' => 'Kirim usulan'])
                </div>
            @endcan
        </x-pm.card>
    </div>
@endsection
