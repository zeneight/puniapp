<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;

new class extends Component {
    use WithPagination;

    #[Layout('layouts.app')]

    // Filter State
    public string $search = '';
    public string $userPilih = '';
    public string $modulPilih = '';
    public string $tanggalMulai = '';

    // Modal Detail State
    public $detailLog = null;

    // Reset pagination setiap kali filter berubah
    public function updatedSearch() { $this->resetPage(); }
    public function updatedUserPilih() { $this->resetPage(); }
    public function updatedModulPilih() { $this->resetPage(); }
    public function updatedTanggalMulai() { $this->resetPage(); }

    public function resetFilter()
    {
        $this->reset(['search', 'userPilih', 'modulPilih', 'tanggalMulai']);
        $this->resetPage();
    }

    public function lihatDetail($id)
    {
        $this->detailLog = Activity::with(['causer', 'subject'])->findOrFail($id);
        $this->js('$flux.modal("detail-aktivitas").show()');
    }

    public function with()
    {
        $logs = Activity::with(['causer'])
            ->when($this->search, function ($query) {
                $query->where('description', 'like', '%' . $this->search . '%')
                      ->orWhereHas('causer', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
            })
            ->when($this->userPilih, function ($query) {
                $query->where('causer_id', $this->userPilih)
                      ->where('causer_type', User::class);
            })
            ->when($this->modulPilih, function ($query) {
                $query->where('log_name', $this->modulPilih);
            })
            ->when($this->tanggalMulai, function ($query) {
                $query->whereDate('created_at', '>=', $this->tanggalMulai);
            })
            ->latest()
            ->paginate(15);

        $daftarUser = User::orderBy('name', 'asc')->get(['id', 'name']);
        
        $daftarModul = Activity::select('log_name')
            ->whereNotNull('log_name')
            ->distinct()
            ->pluck('log_name');

        return [
            'logs' => $logs,
            'daftarUser' => $daftarUser,
            'daftarModul' => $daftarModul,
        ];
    }
}

?>

<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4 mb-6">
        <div>
            <flux:heading size="xl">Riwayat Aktivitas User</flux:heading>
            <flux:subheading>Pantau seluruh aktivitas dan perubahan data yang dilakukan oleh petugas.</flux:subheading>
        </div>
    </div>

    <flux:card class="relative">
        {{-- BARIS FILTER --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-6 pb-5 border-b border-zinc-200 dark:border-zinc-700">
            <div class="lg:col-span-2">
                <flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="Cari aktivitas atau nama user..." />
            </div>

            <div>
                <flux:select wire:model.live="userPilih">
                    <flux:select.option value="">Semua User</flux:select.option>
                    @foreach($daftarUser as $user)
                        <flux:select.option value="{{ $user->id }}">{{ $user->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select wire:model.live="modulPilih">
                    <flux:select.option value="">Semua Modul</flux:select.option>
                    @foreach($daftarModul as $modul)
                        <flux:select.option value="{{ $modul }}">{{ $modul }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="flex items-center gap-2">
                <flux:input wire:model.live="tanggalMulai" type="date" title="Mulai Tanggal" />
                @if($search || $userPilih || $modulPilih || $tanggalMulai)
                    <flux:button wire:click="resetFilter" variant="ghost" icon="x-mark" title="Reset Filter" />
                @endif
            </div>
        </div>

        {{-- TABEL AKTIVITAS MENGGUNAKAN FLUX TABLE --}}
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Waktu</flux:table.column>
                <flux:table.column>Pengguna</flux:table.column>
                <flux:table.column>Modul</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
                <flux:table.column>Keterangan Aktivitas</flux:table.column>
                <flux:table.column>Detail</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($logs as $log)
                    <flux:table.row wire:key="log-{{ $log->id }}">
                        <flux:table.cell class="whitespace-nowrap">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $log->created_at->translatedFormat('d M Y, H:i') }}
                            </div>
                            <div class="text-xs text-zinc-500">
                                {{ $log->created_at->diffForHumans() }}
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="whitespace-nowrap">
                            <div class="flex items-center gap-2.5">
                                @if($log->causer)
                                    <flux:avatar
                                        size="xs"
                                        :src="$log->causer->photo ? asset('storage/' . $log->causer->photo) : null"
                                        :name="$log->causer->name"
                                        :initials="$log->causer->initials()"
                                    />
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $log->causer->name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $log->causer->email }}</div>
                                    </div>
                                @else
                                    <flux:avatar size="xs" name="Sistem" initials="SYS" />
                                    <div class="font-medium text-zinc-500">Sistem / Tamu</div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge color="zinc" size="sm" inset="top bottom">
                                {{ $log->log_name ?? 'Umum' }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $badgeColor = match($log->event) {
                                    'created', 'login'  => 'green',
                                    'updated'           => 'blue',
                                    'deleted'           => 'red',
                                    'logout'            => 'zinc',
                                    default             => 'indigo',
                                };
                                $eventLabel = match($log->event) {
                                    'created' => 'Tambah',
                                    'updated' => 'Ubah',
                                    'deleted' => 'Hapus',
                                    'login'   => 'Login',
                                    'logout'  => 'Logout',
                                    default   => ucfirst($log->event ?? '-'),
                                };
                            @endphp
                            <flux:badge color="{{ $badgeColor }}" size="sm" inset="top bottom">
                                {{ $eventLabel }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <span class="text-zinc-700 dark:text-zinc-300">{{ $log->description }}</span>
                            @if($log->subject_id)
                                <span class="text-xs font-mono text-zinc-400 ml-1">(ID #{{ $log->subject_id }})</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($log->properties && $log->properties->isNotEmpty())
                                <flux:button wire:click="lihatDetail({{ $log->id }})" variant="ghost" size="sm" icon="eye" class="text-indigo-600 hover:text-indigo-700" title="Lihat Perubahan Data" />
                            @else
                                <span class="text-xs text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-8 text-zinc-500">
                            Belum ada riwayat aktivitas yang tercatat.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </flux:card>

    {{-- MODAL DETAIL PERUBAHAN DATA --}}
    <flux:modal name="detail-aktivitas" class="md:w-[650px]">
        @if($detailLog)
        <div class="space-y-4">
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-3">
                <flux:heading size="lg">Detail Log Aktivitas</flux:heading>
                <flux:subheading>
                    Oleh <strong class="text-zinc-800 dark:text-zinc-200">{{ $detailLog->causer->name ?? 'Sistem' }}</strong> 
                    pada {{ $detailLog->created_at->translatedFormat('d M Y, H:i:s') }}
                </flux:subheading>
            </div>

            @php
                $attributes = $detailLog->properties['attributes'] ?? [];
                $old = $detailLog->properties['old'] ?? [];
            @endphp

            @if(!empty($attributes) || !empty($old))
                <div class="overflow-x-auto border border-zinc-200 dark:border-zinc-700 rounded-lg">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 uppercase border-b border-zinc-200 dark:border-zinc-700">
                            <tr>
                                <th class="px-3 py-2.5">Kolom</th>
                                @if(!empty($old))
                                    <th class="px-3 py-2.5 text-red-600 dark:text-red-400">Sebelum</th>
                                @endif
                                @if(!empty($attributes))
                                    <th class="px-3 py-2.5 text-emerald-600 dark:text-emerald-400">Sesudah</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @php
                                $keys = array_unique(array_merge(array_keys($old), array_keys($attributes)));
                            @endphp
                            @foreach($keys as $key)
                                <tr>
                                    <td class="px-3 py-2 font-mono font-semibold text-zinc-700 dark:text-zinc-300">{{ $key }}</td>
                                    @if(!empty($old))
                                        <td class="px-3 py-2 bg-red-50/40 dark:bg-red-900/10 text-zinc-600 dark:text-zinc-400 break-all">
                                            {{ is_array($old[$key] ?? null) ? json_encode($old[$key]) : ($old[$key] ?? '-') }}
                                        </td>
                                    @endif
                                    @if(!empty($attributes))
                                        <td class="px-3 py-2 bg-emerald-50/40 dark:bg-emerald-900/10 text-zinc-800 dark:text-zinc-200 font-medium break-all">
                                            {{ is_array($attributes[$key] ?? null) ? json_encode($attributes[$key]) : ($attributes[$key] ?? '-') }}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="bg-zinc-50 dark:bg-zinc-800/50 p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-700 space-y-2 text-xs">
                    @foreach($detailLog->properties as $propKey => $propVal)
                        <div>
                            <span class="font-semibold text-zinc-500 uppercase">{{ str_replace('_', ' ', $propKey) }}:</span>
                            <span class="font-mono text-zinc-800 dark:text-zinc-200 ml-1 break-all">
                                {{ is_array($propVal) ? json_encode($propVal) : $propVal }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end pt-2">
                <flux:button x-on:click="$flux.modal('detail-aktivitas').close()" variant="ghost">Tutup</flux:button>
            </div>
        </div>
        @endif
    </flux:modal>
</div>