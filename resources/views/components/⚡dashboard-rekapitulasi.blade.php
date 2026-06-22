<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Reactive;
use App\Models\Transaksi;
use App\Models\Kategori;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    #[Reactive]
    public $tahun;

    public function with()
    {
        $user = Auth::user();

        // 1. Ambil semua master kategori asli dari database
        $kategoris = Kategori::orderBy('nama_kategori', 'asc')->get();

        // 2. Ambil data akumulasi transaksi
        $rawTransaksi = Transaksi::selectRaw('jenis_pembayaran_id, periode_bulan, SUM(nominal) as total')
            ->where('periode_tahun', $this->tahun)
            ->when($user->role === 'inputer', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->groupBy('jenis_pembayaran_id', 'periode_bulan')
            ->get();

        // 3. Ubah data ke format Array Matrix & Deteksi Data Tanpa Kategori
        $matrix = [];
        $adaUmum = false;
        
        foreach ($rawTransaksi as $trx) {
            // Jika jenis_pembayaran_id kosong (null), kita paksa anggap ID-nya adalah 0
            $catId = $trx->jenis_pembayaran_id ?? 0; 
            
            $matrix[$catId][$trx->periode_bulan] = (float) $trx->total;
            
            if ($catId === 0) {
                $adaUmum = true; // Tandai bahwa ada transaksi tanpa kategori
            }
        }

        // 4. Jika ada transaksi tanpa kategori, sisipkan Kategori "Umum" ke dalam list
        if ($adaUmum) {
            $kategoriUmum = new Kategori();
            $kategoriUmum->id = 0;
            $kategoriUmum->nama_kategori = 'Umum (Tanpa Kategori)';
            
            // Push akan meletakkan kategori Umum ini di baris paling bawah tabel
            $kategoris->push($kategoriUmum);
        }

        // 5. Hitung Grand Total
        $grandTotalTahunIni = $rawTransaksi->sum('total');

        // 6. Hitung Total per Bulan (kolom paling bawah/footer)
        $totalPerBulan = array_fill(1, 12, 0);
        foreach ($rawTransaksi as $trx) {
            $totalPerBulan[$trx->periode_bulan] += (float) $trx->total;
        }

        return [
            'kategoris' => $kategoris,
            'matrix' => $matrix,
            'totalPerBulan' => $totalPerBulan,
            'grandTotalTahunIni' => $grandTotalTahunIni > 0 ? $grandTotalTahunIni : 1,
        ];
    }
}
?>

<flux:card class="w-full">
    <div class="mb-4">
        <flux:heading size="lg">Rekapitualsi Keuangan Bulanan Tahun {{ $tahun }}</flux:heading>
        <flux:subheading>Akumulasi total pendapatan, rata-rata bulanan, dan persentase kontribusi per jenis punia.</flux:subheading>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 custom-scrollbar">
        <table class="w-full min-w-[1200px] text-left border-collapse text-xs">
            
            <thead>
                <tr class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700 text-zinc-500 font-semibold">
                    <th class="p-3 min-w-[250px]">Uraian Jenis Pungutan</th>
                    @for($m = 1; $m <= 12; $m++)
                        <th class="p-2 text-center w-20">{{ date('M', mktime(0, 0, 0, $m, 1)) }}</th>
                    @endfor
                    <th class="p-2 text-right w-24 bg-zinc-100/50 dark:bg-zinc-800">Total</th>
                    <th class="p-2 text-right w-24">Rata-Rata</th>
                    <th class="p-2 text-center w-20">%</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 text-zinc-700 dark:text-zinc-300">
                @foreach($kategoris as $kat)
                    @php
                        $rowTotal = 0;
                        $bulanAktifCount = 0; // Menghitung bulan yang benar-benar ada transaksi untuk akurasi rata-rata
                    @endphp
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="p-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $kat->nama_kategori }}</td>
                        
                        @for($m = 1; $m <= 12; $m++)
                            @php
                                $nilaiCell = $matrix[$kat->id][$m] ?? 0;
                                $rowTotal += $nilaiCell;
                                if($nilaiCell > 0) $bulanAktifCount++;
                            @endphp
                            <td class="p-2 text-center font-mono {{ $nilaiCell > 0 ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-300 dark:text-zinc-600' }}">
                                {{ $nilaiCell > 0 ? number_format($nilaiCell, 0, ',', '.') : '-' }}
                            </td>
                        @endfor

                        @php
                            $rataRata = $rowTotal / 12; // Atau gunakan ($bulanAktifCount > 0 ? $rowTotal / $bulanAktifCount : 0) jika ingin rata-rata berdasarkan bulan terisi saja
                            $persenKontribusi = ($rowTotal / $grandTotalTahunIni) * 100;
                        @endphp

                        <td class="p-2 text-right font-mono font-bold bg-zinc-50/50 dark:bg-zinc-800/20 text-zinc-900 dark:text-zinc-100">
                            {{ number_format($rowTotal, 0, ',', '.') }}
                        </td>
                        <td class="p-2 text-right font-mono text-zinc-600 dark:text-zinc-400">
                            {{ number_format($rataRata, 0, ',', '.') }}
                        </td>
                        <td class="p-2 text-center font-mono font-medium text-indigo-600 dark:text-indigo-400">
                            {{ number_format($persenKontribusi, 1, ',', '.') }}%
                        </td>
                    </tr>
                @endforeach
            </tbody>

            <tfoot>
                <tr class="bg-emerald-50/50 dark:bg-emerald-950/20 border-t border-b-2 border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-400 font-bold font-mono">
                    <td class="p-3 text-xs text-emerald-800 dark:text-emerald-300 font-sans uppercase tracking-wider">Total Pemasukan</td>
                    
                    @php $sumHorizontalFooter = 0; @endphp
                    @for($m = 1; $m <= 12; $m++)
                        @php $sumHorizontalFooter += $totalPerBulan[$m]; @endphp
                        <td class="p-2 text-center text-sm">
                            {{ $totalPerBulan[$m] > 0 ? number_format($totalPerBulan[$m], 0, ',', '.') : '0' }}
                        </td>
                    @endfor

                    <td class="p-2 text-right text-sm bg-emerald-100/50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                        {{ number_format($sumHorizontalFooter, 0, ',', '.') }}
                    </td>
                    <td class="p-2 text-right text-zinc-500 dark:text-zinc-400">
                        {{ number_format($sumHorizontalFooter / 12, 0, ',', '.') }}
                    </td>
                    <td class="p-2 text-center text-zinc-500 dark:text-zinc-400">100%</td>
                </tr>
            </tfoot>

        </table>
    </div>
</flux:card>