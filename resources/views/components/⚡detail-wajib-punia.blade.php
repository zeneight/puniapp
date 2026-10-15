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

    // --- TAMBAHAN: State untuk modal edit transaksi ---
    public $edit_trx_id = null;
    public $edit_tanggal_bayar;
    public $edit_nominal;
    public $edit_keterangan;
    public $trxDetail = null;

    public function bukaDetailTransaksi($id)
    {
        // Tarik data transaksi, sesuaikan relasi 'user' atau 'petugas' jika ada di model Transaksi
        $this->trxDetail = Transaksi::findOrFail($id);
        
        $this->js('$flux.modal("detail-transaksi").show()');
    }

    public function bukaEditTransaksi($id)
    {
        $trx = Transaksi::findOrFail($id);
        
        $this->edit_trx_id = $trx->id;
        $this->edit_tanggal_bayar = $trx->tanggal_bayar;
        // Ambil nominal (jaga-jaga namanya kolomnya nominal_bayar atau nominal)
        $this->edit_nominal = $trx->nominal_bayar ?? $trx->nominal; 
        $this->edit_keterangan = $trx->keterangan;

        $this->resetErrorBag();
        
        $this->js('$flux.modal("edit-transaksi").show()');
    }

    public function updateTransaksi()
    {
        $this->validate([
            'edit_tanggal_bayar' => 'required|date',
            'edit_nominal' => 'required|numeric|min:0',
            'edit_keterangan' => 'nullable|string',
        ]);

        $trx = Transaksi::findOrFail($this->edit_trx_id);
        
        // Update data dasar
        $trx->tanggal_bayar = $this->edit_tanggal_bayar;
        $trx->keterangan = $this->edit_keterangan;

        // Cek struktur database untuk kolom nominal
        if (array_key_exists('nominal_bayar', $trx->getAttributes())) {
            $trx->nominal_bayar = $this->edit_nominal;
        } else {
            $trx->nominal = $this->edit_nominal;
        }

        $trx->save();

        $this->js('$flux.modal("edit-transaksi").close()');
        \Flux::toast('Data transaksi berhasil diperbarui!', variant: 'success');
        
        // Bersihkan form
        $this->reset(['edit_trx_id', 'edit_tanggal_bayar', 'edit_nominal', 'edit_keterangan']);
    }

    // ----------------------------------------------------

    public function mount($id)
    {
        $this->wpId = $id;

        $this->wp = WajibPunia::with(['kategori', 'jenisUsaha', 'banjar', 'petugas', 'dokumens' => function($query) {
            $query->latest();
        }])->findOrFail($id);

        // Cek kelengkapan data
        $kolomWajib = [
            'alamat'           => 'Alamat Lengkap',
            'kontak_pengelola' => 'Nomor HP',
        ];

        foreach ($kolomWajib as $kolom => $label) {
            if (empty($this->wp->$kolom)) {
                $this->dataKurang[] = $label;
            }
        }

        if ($this->wp->dokumens->isEmpty()) {
            $this->dataKurang[] = 'File Dokumen Pendukung';
        }

        $this->daftarTahun = Transaksi::where('wajib_punia_id', $id)
            ->select('periode_tahun')
            ->distinct()
            ->orderBy('periode_tahun', 'desc')
            ->pluck('periode_tahun')
            ->toArray();

        if (count($this->daftarTahun) > 0) {
            $this->tahunPilih = $this->daftarTahun[0];
        } else {
            $this->tahunPilih = date('Y');
            $this->daftarTahun = [date('Y')]; 
        }
    }

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
    <!-- Tombol Kembali -->
    <div class="mb-6">
        <flux:button href="{{ route('master.wajibpunia') }}" wire:navigate icon="arrow-left" variant="ghost" class="-ml-3">
            Kembali ke Master Data
        </flux:button>
    </div>

    <!-- Alert Data Kurang -->
    @if(count($dataKurang) > 0)
        <div class="mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 text-amber-800 dark:text-amber-300 shadow-sm">
            <div class="flex items-center gap-2 font-bold mb-2 text-sm">
                <flux:icon.exclamation-triangle class="w-5 h-5" />
                Perhatian: Kelengkapan Data Kurang!
            </div>
            <ul class="list-disc list-inside text-sm mt-1 space-y-0.5 opacity-90 ml-1">
                @foreach($dataKurang as $kurang)
                    <li>{{ $kurang }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <!-- ========================================== -->
        <!-- KOLOM KIRI: Profil & File Wajib Punia      -->
        <!-- ========================================== -->
        <div class="col-span-1 space-y-6">
            
            <flux:card class="flex flex-col gap-6">
                <!-- Header Profil -->
                <div class="border-b border-zinc-200 dark:border-zinc-700 pb-5">
                    <div class="flex justify-between items-start mb-2">
                        <flux:heading size="xl" class="leading-tight">{{ $wp->nama }}</flux:heading>
                        <flux:badge color="{{ $wp->is_active == 1 ? 'success' : 'zinc' }}" size="sm" class="shrink-0 ml-2">
                            {{ $wp->is_active == 1 ? 'Aktif' : 'Non-Aktif' }}
                        </flux:badge>
                    </div>
                    <flux:subheading>{{ $wp->kategori->nama_kategori ?? 'Tanpa Kategori' }}</flux:subheading>
                </div>

                <!-- Informasi Utama -->
                <div>
                    <div class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Informasi Utama</div>
                    <div class="grid grid-cols-2 gap-y-4 gap-x-2">
                        <div class="col-span-2 flex justify-between items-center bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-lg border border-zinc-100 dark:border-zinc-700">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">Pagu Dudukan</span>
                            <span class="font-mono font-bold text-lg text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format($wp->pagu_dudukan, 0, ',', '.') }}
                            </span>
                        </div>
                        
                        <div>
                            <span class="block text-[11px] text-zinc-500 mb-0.5">Pemilik</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $wp->pemilik_nama ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="block text-[11px] text-zinc-500 mb-0.5">No. Kontak / WA</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $wp->kontak_pengelola ?? '-' }}</span>
                        </div>
                        
                        <div>
                            <span class="block text-[11px] text-zinc-500 mb-0.5">Jenis Usaha</span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $wp->jenisUsaha->nama_jenis_usaha ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="block text-[11px] text-zinc-500 mb-0.5">Petugas</span>
                            <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ $wp->petugas->name ?? '-' }}</span>
                        </div>

                        <div class="col-span-2">
                            <div>
                                <span class="block text-[11px] text-zinc-500 mb-0.5">No. Registrasi</span>
                                <span class="text-sm font-mono font-medium text-zinc-900 dark:text-zinc-100 break-all block">{{ $wp->no_registrasi ?? '-' }}</span>
                            </div>
                            
                            <div class="pt-2.5">
                                <span class="block text-[11px] text-zinc-500 mb-0.5">Tgl Registrasi</span>
                                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100 block">
                                    {{ $wp->tgl_registrasi ? \Carbon\Carbon::parse($wp->tgl_registrasi)->locale('id')->isoFormat('D MMM YYYY') : '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informasi Lokasi & Peta -->
                <div class="pt-5 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Lokasi Usaha</div>
                    
                    <div class="mb-3">
                        <span class="block text-[11px] text-zinc-500 mb-0.5">Wilayah Banjar</span>
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $wp->banjar->nama_banjar ?? '-' }}</span>
                    </div>
                    <div class="mb-4">
                        <span class="block text-[11px] text-zinc-500 mb-0.5">Alamat Lengkap</span>
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100 leading-snug">{{ $wp->alamat ?? '-' }}</span>
                    </div>

                    <!-- Mini Map Leaflet Terintegrasi -->
                    @if($wp->latitude && $wp->longitude)
                        <div class="rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700 shadow-sm relative z-0" wire:ignore>
                            <div x-data="{
                                map: null,
                                init() {
                                    this.map = L.map($refs.miniMap, {
                                        zoomControl: false,
                                        dragging: false,
                                        scrollWheelZoom: false,
                                        doubleClickZoom: false,
                                        touchZoom: false
                                    }).setView([{{ $wp->latitude }}, {{ $wp->longitude }}], 16);
                                    
                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
                                    L.marker([{{ $wp->latitude }}, {{ $wp->longitude }}]).addTo(this.map);
                                }
                            }">
                                <div x-ref="miniMap" class="h-36 w-full z-0 relative"></div>
                            </div>
                            <!-- Tombol pintasan buka di aplikasi Google Maps -->
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $wp->latitude }},{{ $wp->longitude }}" target="_blank" class="block w-full text-center py-2.5 text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 text-indigo-600 dark:text-indigo-400 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                                Buka di Google Maps ↗
                            </a>
                        </div>
                    @else
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 text-center text-xs text-zinc-500">
                            Titik koordinat lokasi belum ditambahkan.
                        </div>
                    @endif
                </div>

                @if($wp->keterangan)
                <div class="pt-5 border-t border-zinc-200 dark:border-zinc-700">
                    <span class="block text-[11px] text-zinc-500 mb-1">Catatan / Keterangan Khusus</span>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 bg-yellow-50 dark:bg-yellow-900/10 p-3 rounded-lg border border-yellow-100 dark:border-yellow-900/30">{{ $wp->keterangan }}</p>
                </div>
                @endif
            </flux:card>

            <!-- Card Khusus Dokumen -->
            <flux:card>
                <flux:heading size="md" class="mb-4">Dokumen Pendukung</flux:heading>
                @if($wp->dokumens->count() > 0)
                    <div class="space-y-2.5">
                        @foreach($wp->dokumens as $doc)
                            <flux:button href="{{ asset('storage/' . $doc->path_file) }}" target="_blank" icon="document-arrow-down" class="w-full justify-start text-left" variant="outline">
                                <span class="truncate">{{ $doc->nama_file ?? 'Unduh Dokumen ' . $loop->iteration }}</span>
                            </flux:button>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg text-center text-sm text-zinc-500 border border-dashed border-zinc-300 dark:border-zinc-700">
                        Belum ada file dokumen.
                    </div>
                @endif
            </flux:card>

        </div>

        <!-- ========================================== -->
        <!-- KOLOM KANAN: History Pembayaran            -->
        <!-- ========================================== -->
        <div class="col-span-1 lg:col-span-2">
            <flux:card class="h-full">
                <!-- Header Card dengan Dropdown Filter -->
                <div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-4">
                    <div>
                        <flux:heading size="lg">Riwayat Pembayaran</flux:heading>
                        <flux:subheading>Daftar transaksi setoran yang telah dilakukan.</flux:subheading>
                    </div>
                    
                    <div class="w-full sm:w-48">
                        <flux:select wire:model.live="tahunPilih" icon="calendar" size="sm">
                            @foreach($daftarTahun as $tahun)
                                <option value="{{ $tahun }}">Tahun {{ $tahun }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-zinc-600 dark:text-zinc-400">
                        <thead class="text-[11px] text-zinc-500 uppercase bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-semibold rounded-tl-lg">No</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Bulan</th>
                                <th scope="col" class="px-4 py-3 font-semibold">Tgl Bayar</th>
                                <th scope="col" class="px-4 py-3 font-semibold text-right">Nominal</th>
                                <!-- TAMBAHAN KOLOM AKSI -->
                                <th scope="col" class="px-4 py-3 font-semibold text-center rounded-tr-lg">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($this->transaksiDifilter as $index => $trx)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group">
                                    <td class="px-4 py-3.5 text-zinc-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3.5 font-semibold text-zinc-900 dark:text-zinc-200">
                                        {{ \Carbon\Carbon::create()->month($trx->periode_bulan)->locale('id')->isoFormat('MMMM') }}
                                    </td>
                                    <td class="px-4 py-3.5 text-zinc-500">
                                        {{ \Carbon\Carbon::parse($trx->tanggal_bayar)->locale('id')->isoFormat('D MMM YYYY') }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                        Rp {{ number_format($trx->nominal_bayar ?? $trx->nominal, 0, ',', '.') }}
                                    </td>
                                    <!-- TAMBAHAN TOMBOL DETAIL -->
                                    <td class="px-4 py-3.5 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <flux:button wire:click="bukaDetailTransaksi({{ $trx->id }})" size="xs" variant="subtle" icon="document-text" class="text-zinc-500 hover:text-zinc-700">
                                                Detail
                                            </flux:button>
                                            <flux:button wire:click="bukaEditTransaksi({{ $trx->id }})" size="xs" variant="subtle" icon="pencil-square" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                                Edit
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <!-- COLSPAN DIUBAH MENJADI 5 -->
                                    <td colspan="5" class="px-4 py-16 text-center text-zinc-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-12 h-12 bg-zinc-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-3">
                                                <flux:icon.document-magnifying-glass class="w-6 h-6 text-zinc-400" />
                                            </div>
                                            <p class="font-medium text-zinc-600 dark:text-zinc-300">Tidak ada riwayat pembayaran</p>
                                            <p class="text-xs mt-1">Belum ada transaksi tercatat untuk tahun {{ $tahunPilih }}.</p>
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

    <!-- ========================================== -->
    <!-- MODAL DETAIL TRANSAKSI                     -->
    <!-- ========================================== -->
    <flux:modal name="detail-transaksi" class="md:w-[500px]">
        @if($trxDetail)
        <div>
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-5 flex justify-between items-start">
                <div>
                    <flux:heading size="lg">Detail Pembayaran</flux:heading>
                    <flux:subheading>Periode {{ \Carbon\Carbon::create()->month($trxDetail->periode_bulan)->locale('id')->isoFormat('MMMM') }} {{ $trxDetail->periode_tahun }}</flux:subheading>
                </div>
                <flux:badge color="success" size="sm">Berhasil</flux:badge>
            </div>

            <div class="space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800 border-dashed">
                    <span class="text-sm text-zinc-500">No. Transaksi / Kuitansi</span>
                    <span class="font-mono text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $trxDetail->no_transaksi ?? 'TRX-'.str_pad($trxDetail->id, 5, '0', STR_PAD_LEFT) }}</span>
                </div>

                <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800 border-dashed">
                    <span class="text-sm text-zinc-500">Tanggal Pembayaran</span>
                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($trxDetail->tanggal_bayar)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</span>
                </div>

                <!-- Jika Bli punya relasi ke User penginput, hapus komentar di bawah ini -->
                <!--
                <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800 border-dashed">
                    <span class="text-sm text-zinc-500">Petugas Penerima</span>
                    <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ $trxDetail->user->name ?? 'Petugas' }}</span>
                </div>
                -->

                <!-- Jika ada metode pembayaran (Cash/Transfer), sesuaikan fieldnya -->
                @if(isset($trxDetail->metode_pembayaran))
                <div class="flex justify-between items-center py-2 border-b border-zinc-100 dark:border-zinc-800 border-dashed">
                    <span class="text-sm text-zinc-500">Metode</span>
                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $trxDetail->metode_pembayaran }}</span>
                </div>
                @endif

                <div class="flex justify-between items-center py-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg px-3 mt-2 border border-zinc-100 dark:border-zinc-700">
                    <span class="font-semibold text-zinc-700 dark:text-zinc-300">Total Dibayar</span>
                    <span class="font-mono text-xl font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($trxDetail->nominal_bayar ?? $trxDetail->nominal, 0, ',', '.') }}</span>
                </div>

                @if(isset($trxDetail->keterangan) && $trxDetail->keterangan)
                <div class="mt-4">
                    <span class="block text-xs text-zinc-500 mb-1">Catatan Transaksi</span>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300 p-3 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        {{ $trxDetail->keterangan }}
                    </p>
                </div>
                @endif
                
                <!-- Jika Bli menyimpan bukti foto bayar, bisa ditampilkan di sini -->
                @if(isset($trxDetail->bukti_bayar) && $trxDetail->bukti_bayar)
                <div class="mt-4">
                    <span class="block text-xs text-zinc-500 mb-1">Bukti Transfer / Foto</span>
                    <a href="{{ asset('storage/' . $trxDetail->bukti_bayar) }}" target="_blank" class="block w-full text-center py-2 border border-indigo-200 bg-indigo-50 text-indigo-600 rounded-lg text-sm hover:bg-indigo-100 transition-colors">
                        Lihat Lampiran Bukti
                    </a>
                </div>
                @endif
            </div>

            <div class="mt-8 flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Tutup</flux:button>
                </flux:modal.close>
            </div>
        </div>
        @endif
    </flux:modal>

    <!-- ========================================== -->
    <!-- MODAL EDIT TRANSAKSI                       -->
    <!-- ========================================== -->
    <flux:modal name="edit-transaksi" class="md:w-[500px]">
        <form wire:submit.prevent="updateTransaksi" class="space-y-5">
            <div>
                <flux:heading size="lg">Edit Transaksi</flux:heading>
                <flux:subheading>Ubah tanggal pembayaran, nominal, atau catatan transaksi.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="edit_tanggal_bayar" type="date" label="Tanggal Pembayaran" required />
                
                <flux:input wire:model="edit_nominal" type="number" label="Nominal (Rp)" required />
                
                <flux:textarea wire:model="edit_keterangan" label="Catatan / Keterangan Transaksi" rows="3" />
            </div>

            <div class="mt-6 flex justify-end gap-2 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>