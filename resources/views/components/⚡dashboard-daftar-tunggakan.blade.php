<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Reactive;
use App\Models\Transaksi;
use App\Models\WajibPunia;
use App\Models\Banjar;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    #[Reactive]
    public $bulan;

    #[Reactive]
    public $tahun;

    // State untuk Modal "Lihat Semua"
    public bool $showModalSemua = false;
    public string $searchModal = '';
    public string $filterPetugasModal = '';
    public string $filterBanjarModal = '';

    public function bukaModalSemua()
    {
        $this->reset(['searchModal', 'filterPetugasModal', 'filterBanjarModal']);
        $this->showModalSemua = true;
        $this->js('$flux.modal("semua-tunggakan").show()');
    }

    /**
     * Menghitung daftar Wajib Punia yang belum membayar.
     * Jika user bukan admin, data selalu dikunci hanya untuk user tersebut.
     */
    private function ambilDataTunggakan(bool $pakaiFilterModal = false)
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        $daftarTunggakan = collect();
        $modeTahunan = empty($this->bulan);
        $sekarang = Carbon::now();

        if ($modeTahunan) {
            // MODE 1: AKUMULASI TAHUNAN
            $queryWP = WajibPunia::with(['banjar', 'user'])->where('is_active', true);

            // Kunci data jika bukan admin
            if (!$isAdmin) {
                $queryWP->where('user_id', $user->id);
            }

            if ($pakaiFilterModal) {
                if ($this->searchModal) {
                    $queryWP->where(function ($q) {
                        $q->where('nama', 'like', '%' . $this->searchModal . '%')
                          ->orWhere('pemilik_nama', 'like', '%' . $this->searchModal . '%');
                    });
                }
                if ($isAdmin && $this->filterPetugasModal) {
                    $queryWP->where('user_id', $this->filterPetugasModal);
                }
                if ($this->filterBanjarModal) {
                    $queryWP->where('banjar_id', $this->filterBanjarModal);
                }
            }

            $semuaWp = $queryWP->get();

            // Hitung jumlah bayar sekaligus untuk mencegah N+1 query
            $rekapBayar = Transaksi::where('periode_tahun', $this->tahun)
                ->selectRaw('wajib_punia_id, COUNT(*) as total_bayar')
                ->groupBy('wajib_punia_id')
                ->pluck('total_bayar', 'wajib_punia_id');

            $tahunFilter = (int) $this->tahun;

            foreach ($semuaWp as $wp) {
                $hariJatuhTempo = $wp->tgl_registrasi ? (int) date('d', strtotime($wp->tgl_registrasi)) : 15;
                $maxBulanWajibBayar = 0;

                if ($tahunFilter < $sekarang->year) {
                    $maxBulanWajibBayar = 12;
                } elseif ($tahunFilter == $sekarang->year) {
                    $maxBulanWajibBayar = $sekarang->month - 1;

                    $tglJatuhTempoBulanIni = Carbon::create(
                        $sekarang->year,
                        $sekarang->month,
                        min($hariJatuhTempo, $sekarang->daysInMonth)
                    );
                    $batasMulaiMuncul = $tglJatuhTempoBulanIni->copy()->subDays(7);

                    if ($sekarang->greaterThanOrEqualTo($batasMulaiMuncul)) {
                        $maxBulanWajibBayar++;
                    }
                }

                $jmlBayar = $rekapBayar[$wp->id] ?? 0;
                $tunggakan = $maxBulanWajibBayar - $jmlBayar;

                if ($tunggakan > 0) {
                    $wp->jumlah_tunggakan = $tunggakan;
                    $wp->total_hutang = $tunggakan * $wp->pagu_dudukan;
                    $daftarTunggakan->push($wp);
                }
            }

            return $daftarTunggakan->sortByDesc('jumlah_tunggakan')->values();
        }

        // MODE 2: SPESIFIK BULAN
        $idSudahBayar = Transaksi::where('periode_bulan', $this->bulan)
            ->where('periode_tahun', $this->tahun)
            ->pluck('wajib_punia_id');

        $query = WajibPunia::with(['banjar', 'user'])
            ->where('is_active', true)
            ->whereNotIn('id', $idSudahBayar);

        // Kunci data jika bukan admin
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        if ($pakaiFilterModal) {
            if ($this->searchModal) {
                $query->where(function ($q) {
                    $q->where('nama', 'like', '%' . $this->searchModal . '%')
                      ->orWhere('pemilik_nama', 'like', '%' . $this->searchModal . '%');
                });
            }
            if ($isAdmin && $this->filterPetugasModal) {
                $query->where('user_id', $this->filterPetugasModal);
            }
            if ($this->filterBanjarModal) {
                $query->where('banjar_id', $this->filterBanjarModal);
            }
        }

        $daftarWPBelumBayar = $query->get();
        $bulanFilter = (int) $this->bulan;
        $tahunFilter = (int) $this->tahun;

        foreach ($daftarWPBelumBayar as $wp) {
            $hariJatuhTempo = $wp->tgl_registrasi ? (int) date('d', strtotime($wp->tgl_registrasi)) : 15;
            $tglJatuhTempoTarget = Carbon::create(
                $tahunFilter,
                $bulanFilter,
                min($hariJatuhTempo, Carbon::create($tahunFilter, $bulanFilter, 1)->daysInMonth)
            );
            $batasMulaiMuncul = $tglJatuhTempoTarget->copy()->subDays(7);

            if ($sekarang->greaterThanOrEqualTo($batasMulaiMuncul)) {
                $wp->jumlah_tunggakan = 1;
                $wp->total_hutang = $wp->pagu_dudukan;
                $daftarTunggakan->push($wp);
            }
        }

        return $daftarTunggakan->sortByDesc('total_hutang')->values();
    }

    public function with()
    {
        $isAdmin = Auth::user()->role === 'admin';
        $modeTahunan = empty($this->bulan);

        // Data ringkas untuk card dashboard (maksimal 6)
        $hasilCard = $this->ambilDataTunggakan(false);

        // Data lengkap untuk modal (hanya diproses saat modal dibuka)
        $semuaTunggakan = $this->showModalSemua ? $this->ambilDataTunggakan(true) : collect();

        return [
            'isAdmin'          => $isAdmin,
            'daftarTunggakan'  => $hasilCard->take(6),
            'totalTunggakan'   => $hasilCard->count(),
            'semuaTunggakan'   => $semuaTunggakan,
            'totalNominalAll'  => $semuaTunggakan->sum('total_hutang'),
            'daftarPetugas'    => ($this->showModalSemua && $isAdmin) ? User::orderBy('name')->get(['id', 'name']) : collect(),
            'daftarBanjar'     => $this->showModalSemua ? Banjar::orderBy('nama_banjar')->get(['id', 'nama_banjar']) : collect(),
            'modeTahunan'      => $modeTahunan,
        ];
    }
}
?>

<flux:card class="flex flex-col">
    <div class="mb-4 flex items-start justify-between gap-3">
        <div>
            <flux:heading size="lg">
                {{ $modeTahunan ? 'Top Tunggakan Tahun Ini' : 'Belum Membayar' }}
            </flux:heading>
            <flux:subheading>
                {{ $modeTahunan ? 'Akumulasi tunggakan tahun ' . $tahun : 'Periode Bulan ' . $bulan . ' / ' . $tahun }}
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <flux:badge color="warning" icon="exclamation-triangle">
                {{ $modeTahunan ? 'Perhatian' : 'Tunggakan' }}
            </flux:badge>
            <flux:button wire:click="bukaModalSemua" size="xs" variant="subtle" icon="list-bullet" class="text-indigo-600 dark:text-indigo-400">
                Lihat Semua
            </flux:button>
        </div>
    </div>

    <div class="h-[580px] flex-1 space-y-3 overflow-y-auto pr-1 custom-scrollbar">
        @forelse($daftarTunggakan as $tunggakan)
            <a href="{{ route('transaksi.input', ['wp_id' => $tunggakan->id]) }}" wire:navigate class="group flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/30 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-all cursor-pointer">
                <div class="min-w-0 pr-3">
                    <div class="font-semibold text-sm text-zinc-800 dark:text-zinc-200 line-clamp-1 group-hover:text-red-700 dark:group-hover:text-red-300 transition-colors">{{ $tunggakan->nama }}</div>
                    <div class="text-[11px] text-zinc-500 mt-0.5">
                        Br. {{ $tunggakan->banjar->nama_banjar ?? '-' }}
                        <span class="text-zinc-300 dark:text-zinc-600">|</span>
                        <span class="text-indigo-600 dark:text-indigo-400 font-medium">Petugas: {{ $tunggakan->user->name ?? '-' }}</span>
                        @if($modeTahunan) 
                            <span class="font-semibold text-red-500 ml-1">• Nunggak {{ $tunggakan->jumlah_tunggakan }} Bln</span> 
                        @endif
                    </div>
                </div>
                <div class="text-right shrink-0 flex flex-col items-end">
                    <div class="font-mono text-sm text-red-600 dark:text-red-400 font-bold">
                        Rp {{ number_format($tunggakan->total_hutang, 0, ',', '.') }}
                    </div>
                    <div class="text-[10px] font-bold text-red-500 mt-1 opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
                        Input Bayar <flux:icon.arrow-right class="w-3 h-3" stroke-width="3" />
                    </div>
                </div>
            </a>
        @empty
            <div class="text-center py-8">
                <flux:icon.check-circle class="w-10 h-10 mx-auto text-emerald-500 mb-2" />
                <div class="text-sm font-medium text-zinc-600 dark:text-zinc-300">Luar biasa!</div>
                <div class="text-xs text-zinc-500 mt-1">
                    {{ $modeTahunan ? 'Tidak ada tunggakan jatuh tempo di tahun ini.' : 'Tidak ada tunggakan jatuh tempo di periode ini.' }}
                </div>
            </div>
        @endforelse
    </div>

    <!-- Tombol Bawah untuk Lihat Semua -->
    <div class="mt-4 pt-3 border-t border-zinc-200 dark:border-zinc-700">
        <flux:button wire:click="bukaModalSemua" variant="ghost" size="sm" class="w-full justify-center text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20" icon-trailing="arrow-right">
            Lihat Semua Belum Membayar ({{ $totalTunggakan }})
        </flux:button>
    </div>

    <!-- ========================================== -->
    <!-- MODAL LIHAT SEMUA TUNGGAKAN                -->
    <!-- ========================================== -->
    <flux:modal name="semua-tunggakan" class="md:w-[850px]" @close="$set('showModalSemua', false)">
        <div class="flex flex-col max-h-[85vh]">
            <!-- Header Modal -->
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 shrink-0">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pr-8">
                    <div>
                        <flux:heading size="lg">
                            {{ $isAdmin ? 'Daftar Lengkap Belum Membayar (Semua Petugas)' : 'Daftar Lengkap Belum Membayar' }}
                        </flux:heading>
                        <flux:subheading>
                            {{ $modeTahunan ? 'Akumulasi tunggakan tahun ' . $tahun : 'Periode Bulan ' . $bulan . ' / ' . $tahun }}
                        </flux:subheading>
                    </div>
                    <div class="text-left sm:text-right bg-red-50 dark:bg-red-900/20 px-3 py-1.5 rounded-lg border border-red-200 dark:border-red-800/40">
                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400">Total ({{ $semuaTunggakan->count() }} Wajib Punia)</div>
                        <div class="font-mono text-sm font-bold text-red-600 dark:text-red-400">
                            Rp {{ number_format($totalNominalAll, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <!-- Filter di Dalam Modal -->
                <div class="grid grid-cols-1 {{ $isAdmin ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }} gap-2.5 mt-4">
                    <flux:input wire:model.live.debounce.300ms="searchModal" type="search" icon="magnifying-glass" size="sm" placeholder="Cari nama usaha / pemilik..." />

                    @if($isAdmin)
                        <flux:select wire:model.live="filterPetugasModal" size="sm">
                            <option value="">Semua Petugas (Semua User)</option>
                            @foreach($daftarPetugas as $ptg)
                                <option value="{{ $ptg->id }}">{{ $ptg->name }}</option>
                            @endforeach
                        </flux:select>
                    @endif

                    <flux:select wire:model.live="filterBanjarModal" size="sm">
                        <option value="">Semua Banjar</option>
                        @foreach($daftarBanjar as $bnj)
                            <option value="{{ $bnj->id }}">Br. {{ $bnj->nama_banjar }}</option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            <!-- Body Scrollable -->
            <div class="flex-1 overflow-y-auto py-4 pr-1 space-y-2.5 relative">
                <div wire:loading wire:target="searchModal, filterPetugasModal, filterBanjarModal" class="w-full py-2 text-center text-xs text-indigo-500 font-medium">
                    Memuat data...
                </div>

                @forelse($semuaTunggakan as $index => $item)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50/70 dark:bg-zinc-800/40 hover:border-red-300 dark:hover:border-red-700 transition-colors">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono text-zinc-400">{{ $index + 1 }}.</span>
                                <a href="{{ route('master.wajibpunia.detail', $item->id) }}" wire:navigate class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 hover:text-indigo-600 dark:hover:text-indigo-400 truncate">
                                    {{ $item->nama }}
                                </a>
                            </div>
                            <div class="text-xs text-zinc-500 mt-1 pl-5 flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span>Br. {{ $item->banjar->nama_banjar ?? '-' }}</span>
                                <span>•</span>
                                <span class="text-indigo-600 dark:text-indigo-400 font-medium">Petugas: {{ $item->user->name ?? '-' }}</span>
                                @if($modeTahunan)
                                    <span>•</span>
                                    <span class="font-semibold text-red-500">Nunggak {{ $item->jumlah_tunggakan }} Bulan</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-3 pl-5 sm:pl-0 shrink-0">
                            <div class="font-mono text-sm font-bold text-red-600 dark:text-red-400">
                                Rp {{ number_format($item->total_hutang, 0, ',', '.') }}
                            </div>
                            <flux:button href="{{ route('transaksi.input', ['wp_id' => $item->id]) }}" wire:navigate size="xs" variant="primary" icon-trailing="arrow-right">
                                Input Bayar
                            </flux:button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 text-zinc-500">
                        <flux:icon.check-circle class="w-10 h-10 mx-auto text-emerald-500 mb-2" />
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tidak ada data tunggakan ditemukan</p>
                        <p class="text-xs mt-1">Semua Wajib Punia sesuai filter di atas sudah lunas atau belum jatuh tempo.</p>
                    </div>
                @endforelse
            </div>

            <!-- Footer Modal -->
            <div class="shrink-0 pt-3 border-t border-zinc-200 dark:border-zinc-700 flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</flux:card>