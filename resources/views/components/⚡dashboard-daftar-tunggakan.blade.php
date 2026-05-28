<?php

use Livewire\Component;
use App\Models\WajibPunia;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public function with()
    {
        $user = Auth::user();
        $bulanIni = date('n');
        $tahunIni = date('Y');

        // 1. Ambil semua ID Wajib Punia yang SUDAH bayar di bulan & tahun ini
        $sudahBayarIds = Transaksi::where('periode_bulan', $bulanIni)
                                  ->where('periode_tahun', $tahunIni)
                                  ->pluck('wajib_punia_id')
                                  ->toArray();

        // 2. Query data master Wajib Punia yang BELUM bayar (tidak ada di array di atas)
        $queryTunggakan = WajibPunia::with(['banjar', 'kategori'])
                                    ->where('is_active', true)
                                    ->whereNotIn('id', $sudahBayarIds);

        // 3. Filter berdasarkan hak akses petugas lapangan
        if ($user->role === 'inputer') {
            $queryTunggakan->where('user_id', $user->id);
        }

        // Mapping nama bulan Indonesia sederhana
        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return [
            'daftarTunggakan' => $queryTunggakan->orderBy('nama')->get(),
            'namaBulan' => $bulanIndo[$bulanIni],
            'tahun' => $tahunIni
        ];
    }
};
?>

<flux:card>
    <div class="mb-4">
        <flux:heading size="lg">Daftar Belum Bayar (Periode {{ $namaBulan }} {{ $tahun }})</flux:heading>
        <flux:subheading>Wajib punia aktif yang belum menyetorkan iuran dudukan bulan ini.</flux:subheading>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Nama / Tempat Usaha</flux:table.column>
            <flux:table.column>Wilayah</flux:table.column>
            <flux:table.column>Tagihan</flux:table.column>
            <flux:table.column>Kontak</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($daftarTunggakan as $wp)
            <flux:table.row>
                <flux:table.cell>
                    <div class="font-semibold text-zinc-800 dark:text-white">{{ $wp->nama }}</div>
                    <div class="text-xs text-zinc-400">{{ $wp->kategori->nama_kategori }}</div>
                </flux:table.cell>
                <flux:table.cell>Br. {{ $wp->banjar->nama_banjar }}</flux:table.cell>
                <flux:table.cell class="font-mono text-amber-600 dark:text-amber-400 font-semibold">
                    Rp {{ number_format($wp->pagu_dudukan, 0, ',', '.') }}
                </flux:table.cell>
                <flux:table.cell>
                    @if($wp->kontak_pengelola)
                        <span class="text-sm text-zinc-600 dark:text-zinc-300">{{ $wp->kontak_pengelola }}</span>
                    @else
                        <span class="text-xs italic text-zinc-400">Tidak ada kontak</span>
                    @endif
                </flux:table.cell>
            </flux:table.row>
            @empty
            <flux:table.row>
                <flux:table.cell colspan="4" class="text-center text-emerald-600 py-4 font-medium">
                    🎉 Luar biasa! Semua wajib punia di bawah wewenang Anda telah melunasi pembayaran bulan ini.
                </flux:table.cell>
            </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</flux:card>