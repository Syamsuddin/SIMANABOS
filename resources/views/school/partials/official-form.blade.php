{{-- Variabel: $action, $submit --}}
@php $roleOptions = collect(\App\Enums\OfficialRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all(); @endphp
<form method="POST" action="{{ $action }}" novalidate class="grid gap-4 sm:grid-cols-2" x-data="{ busy: false }" @submit="busy = true">
    @csrf
    <x-pm.select name="role" label="Peran" required :options="$roleOptions" />
    <x-pm.text-field name="started_at" label="Mulai menjabat" type="date" required />
    <x-pm.text-field name="name" label="Nama lengkap" required maxlength="150" />
    <x-pm.text-field name="nip" label="NIP" optional mono maxlength="18" inputmode="numeric" hint="18 digit angka." />
    <div class="sm:col-span-2">
        <x-pm.button type="submit" variant="primary" x-bind:disabled="busy" x-bind:aria-busy="busy">{{ $submit }}</x-pm.button>
    </div>
</form>
