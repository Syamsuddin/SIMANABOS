{{-- Riwayat pengelola. Variabel: $officials, $approveRoute? (admin), $rejectRoute? (admin) --}}
@if ($officials->isEmpty())
    <x-pm.empty-state icon="users" title="Belum ada pengelola" description="Usulkan kepala sekolah, bendahara, dan operator di formulir berikut." />
@else
    <x-pm.data-table caption="Riwayat pengelola sekolah">
        <x-slot:head>
            <tr>
                <th scope="col" class="px-5 py-3">Peran</th>
                <th scope="col" class="px-5 py-3">Nama</th>
                <th scope="col" class="px-5 py-3">NIP</th>
                <th scope="col" class="px-5 py-3">Menjabat</th>
                <th scope="col" class="px-5 py-3">Status</th>
                @isset($admin)<th scope="col" class="px-5 py-3"><span class="sr-only">Aksi</span></th>@endisset
            </tr>
        </x-slot:head>
        @foreach ($officials as $official)
            <tr>
                <td class="px-5 py-3">{{ $official->role->label() }}</td>
                <td class="px-5 py-3">{{ $official->name }}</td>
                <td class="px-5 py-3 font-mono text-mono">{{ $official->nip ?? '—' }}</td>
                <td class="px-5 py-3">{{ $official->started_at->translatedFormat('d M Y') }} – {{ $official->ended_at?->translatedFormat('d M Y') ?? 'sekarang' }}</td>
                <td class="px-5 py-3">
                    @if ($official->isPending())
                        <x-pm.badge tone="warning" icon="clock">Menunggu pengesahan</x-pm.badge>
                    @elseif ($official->ended_at === null)
                        <x-pm.badge tone="success" icon="check-circle">Menjabat</x-pm.badge>
                    @else
                        <x-pm.badge tone="neutral" icon="clock">Selesai menjabat</x-pm.badge>
                    @endif
                </td>
                @isset($admin)
                    <td class="px-5 py-3">
                        @if ($official->isPending())
                            <div class="flex justify-end gap-2">
                                <form method="POST" action="{{ route('admin.officials.approve', $official) }}">
                                    @csrf
                                    <x-pm.button type="submit" size="sm" variant="primary">Sahkan</x-pm.button>
                                </form>
                                <x-pm.modal
                                    :id="'reject-'.$official->id"
                                    title="Tolak usulan pengelola?"
                                    tone="danger"
                                    trigger="Tolak"
                                    trigger-size="sm"
                                    confirm="Tolak usulan"
                                    :action="route('admin.officials.reject', $official)"
                                    method="DELETE"
                                >Usulan {{ $official->role->label() }} atas nama {{ $official->name }} akan dibuang. Pengelola yang menjabat tidak berubah.</x-pm.modal>
                            </div>
                        @endif
                    </td>
                @endisset
            </tr>
        @endforeach
    </x-pm.data-table>
@endif
