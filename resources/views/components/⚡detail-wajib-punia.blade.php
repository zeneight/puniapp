<?php

use Livewire\Component;
use App\Models\WajibPunia;
use App\Models\Transaksi;
use Livewire\Attributes\Computed;

new class extends Component
{
    public $wpId;
    public $wp;
    public $dataKurang = [];

    // State untuk filter
    public $tahunPilih;
    public $daftarTahun = [];

    // Fungsi mount akan menangkap parameter {id} dari Route atau saat dipanggil
    public function mount($id)
    {
        // Simpan ID Wajib Punia untuk digunakan di computed property
        $this->wpId = $id;

        // Ambil data beserta relasi kategori dan transaksi (urut terbaru)
        $this->wp = WajibPunia::with(['kategori', 'dokumens' => function($query) {
            $query->latest();
        }])->findOrFail($id);

        // dd($this->wp);

        // Cek kelengkapan data
        $kolomWajib = [
            'alamat'            => 'Alamat Lengkap',
            'kontak_pengelola'  => 'Nomor HP',
            // 'file_dokumen'      => 'File Dokumen Pendukung'
        ];

        foreach ($kolomWajib as $kolom => $label) {
            // Jika kolom di database kosong (null atau string kosong)
            if (empty($this->wp->$kolom)) {
                $this->dataKurang[] = $label;
            }
        }

        // Cek dari relasi dokumens
        if ($this->wp->dokumens->isEmpty()) {
            $this->dataKurang[] = 'File Dokumen Pendukung';
        }

        // 2. Ambil daftar tahun yang pernah dibayar oleh user ini untuk isi Dropdown
        $this->daftarTahun = Transaksi::where('wajib_punia_id', $id)
            ->select('periode_tahun')
            ->distinct()
            ->orderBy('periode_tahun', 'desc')
            ->pluck('periode_tahun')
            ->toArray();

        // 3. Set default tahun terpilih (Tahun terbaru atau tahun berjalan)
        if (count($this->daftarTahun) > 0) {
            $this->tahunPilih = $this->daftarTahun[0];
        } else {
            $this->tahunPilih = date('Y');
            $this->daftarTahun = [date('Y')]; // Jaga-jaga jika kosong, tampilkan tahun ini
        }
    }

    // Fungsi reaktif untuk menarik transaksi berdasarkan tahun yang dipilih
    #[Computed]
    public function transaksiDifilter()
    {
        return Transaksi::where('wajib_punia_id', $this->wpId)
            ->where('periode_tahun', $this->tahunPilih)
            ->orderBy('periode_bulan', 'desc')
            ->get();
    }
};
?>

<div>
    <!-- Tombol Kembali menggunakan Flux -->
    <div class="mb-6">
        <flux:button href="{{ route('master.wajibpunia') }}" wire:navigate icon="arrow-left" variant="ghost">
            Kembali ke Master Data
        </flux:button>
    </div>

    <!-- Alert Peringatan Jika Data Kurang (Tailwind Native) -->
    @if(count($dataKurang) > 0)
        <div class="mb-6 p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-300">
            <div class="flex items-center gap-2 font-semibold mb-2">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                Perhatian: Data Belum Lengkap!
            </div>
            <ul class="list-disc list-inside text-sm mt-1">
                @foreach($dataKurang as $kurang)
                    <li>{{ $kurang }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Layout Grid (1 Kolom di HP, 3 Kolom di PC) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- KOLOM KIRI: Profil & File -->
        <div class="col-span-1">
            <flux:card>
                <div class="mb-6 border-b border-gray-200 dark:border-gray-700 pb-4">
                    <flux:heading size="xl">{{ $wp->nama }}</flux:heading>
                    <flux:subheading>{{ $wp->kategori->nama_kategori ?? 'Tanpa Kategori' }}</flux:subheading>
                </div>

                <div class="space-y-4 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 dark:text-gray-400">Status</span>
                        <flux:badge color="{{ $wp->is_active == 1 ? 'green' : 'zinc' }}" size="sm" inset="top bottom">
                            {{ $wp->is_active == 1 ? 'Aktif' : 'Non-Aktif' }}
                        </flux:badge>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 dark:text-gray-400">Pagu Dudukan</span>
                        <span class="font-bold text-gray-900 dark:text-white">Rp {{ number_format($wp->pagu_dudukan, 0, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500 dark:text-gray-400 text-xs mb-1">No. HP</span>
                        <span class="text-gray-900 dark:text-gray-200">{{ $wp->kontak_pengelola ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500 dark:text-gray-400 text-xs mb-1">Alamat Lengkap</span>
                        <span class="text-gray-900 dark:text-gray-200">{{ $wp->alamat ?? '-' }}</span>
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <flux:heading size="md" class="mb-3">Dokumen Pendukung</flux:heading>
                    
                    @if($wp->dokumens->count() > 0)
                        <div class="space-y-2">
                            @foreach($wp->dokumens as $doc)
                                <!-- Sesuaikan kolom 'file_path' dengan nama kolom lokasi file di tabel dokumenmu -->
                                <flux:button href="{{ asset('storage/' . $doc->path_file) }}" target="_blank" icon="arrow-down-tray" class="w-full" variant="outline">
                                    <!-- Sesuaikan kolom 'nama_dokumen' dengan kolom namamu, atau pakai nama default -->
                                    {{ $doc->nama_file ?? 'Unduh Dokumen ' . $loop->iteration }}
                                </flux:button>
                            @endforeach
                        </div>
                    @else
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center text-sm text-gray-500 dark:text-gray-400 italic">
                            Belum ada dokumen yang diunggah
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>

        <!-- KOLOM KANAN: History Pembayaran -->
        <div class="col-span-1 md:col-span-2">
            <flux:card>
                <!-- Header Card dengan Dropdown Filter -->
                <div class="mb-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                    <div>
                        <flux:heading size="lg">Riwayat Pembayaran</flux:heading>
                        <flux:subheading>Daftar transaksi yang telah dilakukan</flux:subheading>
                    </div>
                    
                    <!-- Dropdown Filter Tahun -->
                    <div class="w-full sm:w-40">
                        <flux:select wire:model.live="tahunPilih" size="sm">
                            @foreach($daftarTahun as $tahun)
                                <option value="{{ $tahun }}">Tahun {{ $tahun }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
                
                <div class="overflow-x-auto border rounded-lg dark:border-gray-700">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-4 py-3">No</th>
                                <th scope="col" class="px-4 py-3">Bulan</th>
                                <th scope="col" class="px-4 py-3">Tgl Bayar</th>
                                <th scope="col" class="px-4 py-3 text-right">Nominal</th>
                                <!-- <th scope="col" class="px-4 py-3 text-center">Status</th> -->
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- PANGGIL COMPUTED PROPERTY DI SINI -->
                            @forelse($this->transaksiDifilter as $index => $trx)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-3">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-200">
                                        {{ \Carbon\Carbon::create()->month($trx->periode_bulan)->translatedFormat('F') }}
                                    </td>
                                    <!-- Kolom tahun dihapus karena sudah diwakili filter dropdown -->
                                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($trx->tanggal_bayar)->translatedFormat('d M Y') }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                                        Rp {{ number_format($trx->nominal, 0, ',', '.') }}
                                    </td>
                                    <!-- <td class="px-4 py-3 text-center">
                                        <flux:badge color="success" size="sm">Lunas</flux:badge>
                                    </td> -->
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="size-10 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                            <p>Tidak ada riwayat pembayaran di tahun {{ $tahunPilih }}.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>
    </div>
</div>