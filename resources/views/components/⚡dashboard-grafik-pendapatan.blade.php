<?php

use Livewire\Component;
use Livewire\Attributes\Reactive;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    #[Reactive]
    public $bulan;

    #[Reactive]
    public $tahun;

    public function with() {
        $user = Auth::user();

        // MODE 1: TAMPILKAN TREN TAHUNAN (JIKA "SEMUA BULAN" DIPILIH)
        if (empty($this->bulan)) {
            $query = Transaksi::selectRaw('periode_bulan, SUM(nominal) as total')
                              ->where('periode_tahun', $this->tahun)
                              ->groupBy('periode_bulan');
            
            if ($user->role === 'inputer') {
                $query->where('user_id', $user->id);
            }

            $dataDb = $query->get()->keyBy('periode_bulan');
            
            $chartData = [];
            $maxTotal = 1;
            $namaBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

            for ($i = 1; $i <= 12; $i++) {
                $total = $dataDb->has($i) ? $dataDb[$i]->total : 0;
                $chartData[] = [
                    'label' => $namaBulan[$i-1],
                    'total' => $total
                ];
                if ($total > $maxTotal) {
                    $maxTotal = $total;
                }
            }

            return [
                'tipe' => 'tren_tahunan',
                'chartData' => $chartData,
                'maxTotal' => $maxTotal
            ];
        } 
        
        // MODE 2: TAMPILKAN KOMPOSISI PER BANJAR (JIKA BULAN SPESIFIK DIPILIH)
        else {
            $query = Transaksi::with('wajibPunia.banjar')
                              ->where('periode_bulan', $this->bulan)
                              ->where('periode_tahun', $this->tahun);

            if ($user->role === 'inputer') {
                $query->where('user_id', $user->id);
            }

            $transaksis = $query->get();

            // Kelompokkan total nominal berdasarkan nama Banjar
            $komposisi = $transaksis->groupBy(function($item) {
                return $item->wajibPunia && $item->wajibPunia->banjar 
                       ? 'Banjar ' . $item->wajibPunia->banjar->nama_banjar 
                       : 'Wilayah Lainnya';
            })->map(function($group) {
                return $group->sum('nominal');
            })->sortDesc(); // Urutkan dari penyumbang terbesar

            $totalSemua = $komposisi->sum() ?: 1; // Cegah pembagian dengan nol

            return [
                'tipe' => 'komposisi_banjar',
                'chartData' => $komposisi,
                'totalSemua' => $totalSemua
            ];
        }
    }
};
?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="lg">
                {{ $tipe === 'tren_tahunan' ? 'Tren Pendapatan Tahunan' : 'Komposisi Pendapatan per Banjar' }}
            </flux:heading>
            <flux:subheading>
                {{ $tipe === 'tren_tahunan' ? 'Total per bulan sepanjang tahun ' . $tahun : 'Distribusi penerimaan pada bulan ' . $bulan . ' / ' . $tahun }}
            </flux:subheading>
        </div>
        
        <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 rounded-md">
            @if($tipe === 'tren_tahunan')
                <flux:icon.chart-bar class="w-5 h-5" />
            @else
                <flux:icon.chart-pie class="w-5 h-5" />
            @endif
        </div>
    </div>

    @if($tipe === 'tren_tahunan')
        <div class="flex items-end justify-between gap-1 h-56 w-full pt-4">
            @foreach($chartData as $data)
                @php 
                    $tinggi = max(2, ($data['total'] / $maxTotal) * 100); 
                @endphp
                <div class="flex flex-col justify-end items-center flex-1 group h-full relative">
                    <div class="absolute -top-8 opacity-0 group-hover:opacity-100 transition-opacity text-[10px] bg-zinc-800 text-white px-2 py-1 rounded whitespace-nowrap z-20 pointer-events-none">
                        Rp {{ number_format($data['total'], 0, ',', '.') }}
                    </div>
                    
                    <div class="w-full max-w-[32px] bg-indigo-500 hover:bg-indigo-400 dark:bg-indigo-600 dark:hover:bg-indigo-500 rounded-t-sm transition-all duration-500" style="height: {{ $tinggi }}%;"></div>
                    
                    <div class="text-[10px] text-zinc-500 mt-2 font-medium">
                        {{ $data['label'] }}
                    </div>
                </div>
            @endforeach
        </div>

    @else
        @if($chartData->count() > 0)
            <div class="space-y-4 max-h-56 overflow-y-auto pr-2">
                @foreach($chartData as $namaBanjar => $total)
                    @php 
                        $persen = round(($total / $totalSemua) * 100, 1); 
                    @endphp
                    <div>
                        <div class="flex justify-between items-end mb-1">
                            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ $namaBanjar }}</span>
                            <span class="text-xs font-mono text-zinc-500">
                                Rp {{ number_format($total, 0, ',', '.') }} ({{ $persen }}%)
                            </span>
                        </div>
                        <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-indigo-500 dark:bg-indigo-400 h-2.5 rounded-full transition-all duration-700" style="width: {{ $persen }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="h-48 flex flex-col items-center justify-center text-zinc-400 text-sm border-2 border-dashed border-zinc-100 dark:border-zinc-800 rounded-lg">
                <flux:icon.archive-box-x-mark class="w-8 h-8 mb-2 text-zinc-300" />
                Belum ada transaksi bulan ini.
            </div>
        @endif
    @endif
</div>