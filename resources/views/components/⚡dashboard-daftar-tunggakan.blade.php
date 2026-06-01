<?php

use Livewire\Component;
use Livewire\Attributes\Reactive;
use App\Models\Transaksi;
use App\Models\WajibPunia;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    #[Reactive]
    public $bulan;

    #[Reactive]
    public $tahun;

    public function with() {
        $user = Auth::user();
        $daftarTunggakan = collect();
        $modeTahunan = empty($this->bulan);

        if ($modeTahunan) {
            // MODE 1: SEMUA BULAN (Hitung Akumulasi Tunggakan dalam 1 Tahun)
            // Jika tahun yang dipilih adalah tahun ini, batasnya adalah bulan ini (misal: 6). Jika tahun lalu, batasnya 12 bulan.
            $targetBulan = ($this->tahun == date('Y')) ? (int) date('n') : 12;

            $queryWP = WajibPunia::with('banjar')->where('is_active', true);
            if ($user->role === 'inputer') $queryWP->where('user_id', $user->id);
            
            $semuaWp = $queryWP->get();

            foreach($semuaWp as $wp) {
                // Hitung berapa kali orang ini sudah bayar di tahun tersebut
                $jmlBayar = Transaksi::where('wajib_punia_id', $wp->id)
                                     ->where('periode_tahun', $this->tahun)
                                     ->count();
                
                $tunggakan = $targetBulan - $jmlBayar;
                
                if ($tunggakan > 0) {
                    $wp->jumlah_tunggakan = $tunggakan;
                    $wp->total_hutang = $tunggakan * $wp->pagu_dudukan;
                    $daftarTunggakan->push($wp);
                }
            }
            
            // Urutkan dari yang tunggakannya paling besar, ambil 6 teratas
            $daftarTunggakan = $daftarTunggakan->sortByDesc('jumlah_tunggakan')->take(6);
            
        } else {
            // MODE 2: SPESIFIK BULAN (Cek siapa yang belum bayar di bulan tersebut)
            $idSudahBayar = Transaksi::where('periode_bulan', $this->bulan)
                                     ->where('periode_tahun', $this->tahun)
                                     ->pluck('wajib_punia_id');

            $query = WajibPunia::with('banjar')
                               ->where('is_active', true)
                               ->whereNotIn('id', $idSudahBayar);

            if ($user->role === 'inputer') $query->where('user_id', $user->id);
            
            $daftarWP = $query->take(6)->get();

            // Format agar struktur datanya sama dengan Mode 1
            foreach($daftarWP as $wp) {
                $wp->jumlah_tunggakan = 1;
                $wp->total_hutang = $wp->pagu_dudukan;
                $daftarTunggakan->push($wp);
            }
        }

        return [
            'daftarTunggakan' => $daftarTunggakan,
            'modeTahunan' => $modeTahunan
        ];
    }
};
?>

<flux:card>
    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="lg">
                {{ $modeTahunan ? 'Top Tunggakan Tahun Ini' : 'Belum Membayar' }}
            </flux:heading>
            <flux:subheading>
                {{ $modeTahunan ? 'Akumulasi tunggakan sepanjang tahun ' . $tahun : 'Periode Bulan ' . $bulan . ' / ' . $tahun }}
            </flux:subheading>
        </div>
        <flux:badge color="warning" icon="exclamation-triangle">
            {{ $modeTahunan ? 'Perhatian' : 'Tunggakan' }}
        </flux:badge>
    </div>

    <div class="space-y-4 max-h-[280px] overflow-y-auto pr-1">
        @forelse($daftarTunggakan as $tunggakan)
            <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/30 rounded-lg">
                <div>
                    <div class="font-semibold text-sm text-zinc-800 dark:text-zinc-200 line-clamp-1">{{ $tunggakan->nama }}</div>
                    <div class="text-[11px] text-zinc-500 mt-0.5">
                        Br. {{ $tunggakan->banjar->nama_banjar ?? '-' }} 
                        @if($modeTahunan) 
                            <span class="font-semibold text-red-500 ml-1">• Nunggak {{ $tunggakan->jumlah_tunggakan }} Bln</span> 
                        @endif
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <div class="font-mono text-sm text-red-600 dark:text-red-400 font-bold">
                        Rp {{ number_format($tunggakan->total_hutang, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-8">
                <flux:icon.check-circle class="w-10 h-10 mx-auto text-emerald-500 mb-2" />
                <div class="text-sm font-medium text-zinc-600">Luar biasa!</div>
                <div class="text-xs text-zinc-500">
                    {{ $modeTahunan ? 'Semua tagihan lunas di tahun ini.' : 'Semua Wajib Punia sudah lunas di periode ini.' }}
                </div>
            </div>
        @endforelse
    </div>
</flux:card>