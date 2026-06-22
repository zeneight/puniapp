<?php

namespace App\Livewire; // Pastikan namespace ada

use Livewire\Component;
use Livewire\Attributes\Reactive;
use App\Models\Transaksi;
use App\Models\WajibPunia;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    #[Reactive]
    public $bulan;

    #[Reactive]
    public $tahun;

    public function with() {
        $user = Auth::user();
        $daftarTunggakan = collect();
        $modeTahunan = empty($this->bulan);
        $sekarang = Carbon::now();

        if ($modeTahunan) {
            // MODE 1: AKUMULASI TAHUNAN
            $queryWP = WajibPunia::with('banjar', 'user')->where('is_active', true);
            if ($user->role === 'inputer') $queryWP->where('user_id', $user->id);
            
            $semuaWp = $queryWP->get();

            foreach($semuaWp as $wp) {
                // Ambil tanggal registrasi sebagai patokan jatuh tempo (Default tgl 15 jika kosong)
                $hariJatuhTempo = $wp->tgl_registrasi ? (int) date('d', strtotime($wp->tgl_registrasi)) : 15;
                
                $maxBulanWajibBayar = 0;
                $tahunFilter = (int) $this->tahun;

                if ($tahunFilter < $sekarang->year) {
                    $maxBulanWajibBayar = 12; // Tahun lalu, wajib 12 bulan lunas
                } elseif ($tahunFilter == $sekarang->year) {
                    $maxBulanWajibBayar = $sekarang->month - 1; // Bulan-bulan sebelumnya pasti sudah jatuh tempo
                    
                    // Cek KHUSUS untuk bulan berjalan: Apakah sudah masuk H-7 dari tanggal jatuh tempo?
                    // (Gunakan min() untuk mencegah error jika tgl 31 tapi bulan hanya sampai 30)
                    $tglJatuhTempoBulanIni = Carbon::create($sekarang->year, $sekarang->month, min($hariJatuhTempo, $sekarang->daysInMonth));
                    $batasMulaiMuncul = $tglJatuhTempoBulanIni->copy()->subDays(7);
                    
                    if ($sekarang->greaterThanOrEqualTo($batasMulaiMuncul)) {
                        $maxBulanWajibBayar++; // Tambah 1 tagihan karena sudah masuk masa tenggang H-7
                    }
                }

                $jmlBayar = Transaksi::where('wajib_punia_id', $wp->id)
                                     ->where('periode_tahun', $this->tahun)
                                     ->count();
                
                $tunggakan = $maxBulanWajibBayar - $jmlBayar;
                
                if ($tunggakan > 0) {
                    $wp->jumlah_tunggakan = $tunggakan;
                    $wp->total_hutang = $tunggakan * $wp->pagu_dudukan;
                    $daftarTunggakan->push($wp);
                }
            }
            
            $daftarTunggakan = $daftarTunggakan->sortByDesc('jumlah_tunggakan')->take(6);
            
        } else {
            // MODE 2: SPESIFIK BULAN
            $idSudahBayar = Transaksi::where('periode_bulan', $this->bulan)
                                     ->where('periode_tahun', $this->tahun)
                                     ->pluck('wajib_punia_id');

            $query = WajibPunia::with('banjar', 'user')
                               ->where('is_active', true)
                               ->whereNotIn('id', $idSudahBayar);

            if ($user->role === 'inputer') $query->where('user_id', $user->id);
            
            $daftarWPBelumBayar = $query->get();

            $bulanFilter = (int) $this->bulan;
            $tahunFilter = (int) $this->tahun;

            foreach($daftarWPBelumBayar as $wp) {
                $hariJatuhTempo = $wp->tgl_registrasi ? (int) date('d', strtotime($wp->tgl_registrasi)) : 15;
                $tglJatuhTempoTarget = Carbon::create($tahunFilter, $bulanFilter, min($hariJatuhTempo, Carbon::create($tahunFilter, $bulanFilter, 1)->daysInMonth));
                $batasMulaiMuncul = $tglJatuhTempoTarget->copy()->subDays(7);

                // HANYA masukkan ke daftar jika hari ini sudah melewati H-7 dari bulan yang dicek
                if ($sekarang->greaterThanOrEqualTo($batasMulaiMuncul)) {
                    $wp->jumlah_tunggakan = 1;
                    $wp->total_hutang = $wp->pagu_dudukan;
                    $daftarTunggakan->push($wp);
                }
            }

            $daftarTunggakan = $daftarTunggakan->sortByDesc('total_hutang')->take(6);
        }

        return [
            'daftarTunggakan' => $daftarTunggakan,
            'modeTahunan' => $modeTahunan
        ];
    }
}
?>

<flux:card>
    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="lg">
                {{ $modeTahunan ? 'Top Tunggakan Tahun Ini' : 'Belum Membayar' }}
            </flux:heading>
            <flux:subheading>
                {{ $modeTahunan ? 'Akumulasi tunggakan tahun ' . $tahun : 'Periode Bulan ' . $bulan . ' / ' . $tahun }}
            </flux:subheading>
        </div>
        <flux:badge color="warning" icon="exclamation-triangle">
            {{ $modeTahunan ? 'Perhatian' : 'Tunggakan' }}
        </flux:badge>
    </div>

    <div class="space-y-3 max-h-[280px] overflow-y-auto pr-1 custom-scrollbar">
        @forelse($daftarTunggakan as $tunggakan)
            <a href="{{ route('transaksi.input', ['wp_id' => $tunggakan->id]) }}" wire:navigate class="group flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/30 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-all cursor-pointer">
                <div>
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
                <div class="text-sm font-medium text-zinc-600">Luar biasa!</div>
                <div class="text-xs text-zinc-500 mt-1">
                    {{ $modeTahunan ? 'Tidak ada tunggakan jatuh tempo di tahun ini.' : 'Tidak ada tunggakan jatuh tempo di periode ini.' }}
                </div>
            </div>
        @endforelse
    </div>
</flux:card>