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

    public function with()
    {
        $user = Auth::user();
        $queryTransaksi = Transaksi::query();
        $queryWajibPunia = WajibPunia::where('is_active', true);

        if ($user->role === 'inputer') {
            $queryTransaksi->where('user_id', $user->id);
            $queryWajibPunia->where('user_id', $user->id);
        }

        // Hitungan Default (Tahun dan Total WP)
        $totalTahunIni = (clone $queryTransaksi)->where('periode_tahun', $this->tahun)->sum('nominal');
        $jumlahWajibPunia = $queryWajibPunia->count();

        // Hitungan Dinamis untuk Kotak Biru (Card 1)
        if (empty($this->bulan)) {
            $infoCard1 = [
                'label' => 'Total Transaksi Tahun ' . $this->tahun,
                'value' => (clone $queryTransaksi)->where('periode_tahun', $this->tahun)->count() . ' Kali',
            ];
        } else {
            $totalBulanIni = (clone $queryTransaksi)
                ->where('periode_bulan', $this->bulan)
                ->where('periode_tahun', $this->tahun)
                ->sum('nominal');
            $infoCard1 = [
                'label' => 'Penerimaan Bulan ' . $this->bulan,
                'value' => 'Rp ' . number_format($totalBulanIni, 0, ',', '.'),
            ];
        }

        return [
            'infoCard1' => $infoCard1,
            'totalTahunIni' => $totalTahunIni,
            'jumlahWajibPunia' => $jumlahWajibPunia,
        ];
    }
};
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <flux:card>
        <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 text-blue-600 rounded-lg dark:bg-blue-900/50 dark:text-blue-400">
                @if(empty($bulan))
                    <flux:icon.receipt-percent class="w-8 h-8" />
                @else
                    <flux:icon.banknotes class="w-8 h-8" />
                @endif
            </div>
            <div>
                <div class="text-sm font-medium text-zinc-500">{{ $infoCard1['label'] }}</div>
                <div class="text-2xl font-bold text-zinc-800 dark:text-white">
                    {{ $infoCard1['value'] }}
                </div>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center gap-4">
            <div class="p-3 bg-emerald-100 text-emerald-600 rounded-lg dark:bg-emerald-900/50 dark:text-emerald-400">
                <flux:icon.chart-bar class="w-8 h-8" />
            </div>
            <div>
                <div class="text-sm font-medium text-zinc-500">Total Tahun {{ $tahun }}</div>
                <div class="text-2xl font-bold text-zinc-800 dark:text-white">
                    Rp {{ number_format($totalTahunIni, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-100 text-amber-600 rounded-lg dark:bg-amber-900/50 dark:text-amber-400">
                <flux:icon.users class="w-8 h-8" />
            </div>
            <div>
                <div class="text-sm font-medium text-zinc-500">Wajib Punia Aktif</div>
                <div class="text-2xl font-bold text-zinc-800 dark:text-white">
                    {{ $jumlahWajibPunia }} <span class="text-sm font-normal text-zinc-500">Tempat/Orang</span>
                </div>
            </div>
        </div>
    </flux:card>
</div>