<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\WajibPunia;
use App\Models\Transaksi;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $wpId;
    public $wp;
    public $dataKurang = [];

    // State untuk filter
    public $tahunPilih;
    public $daftarTahun = [];

    // --- State untuk modal detail & edit transaksi ---
    public $edit_trx_id = null;
    public $edit_tanggal_bayar;
    public $edit_nominal;
    public $edit_keterangan;
    public $edit_bukti_lama = null;
    public $edit_bukti_baru = null;
    public $hapus_bukti_lama = false;
    public $trxDetail = null;

    public function bukaDetailTransaksi($id)
    {
        $this->trxDetail = Transaksi::findOrFail($id);
        
        $this->js('$flux.modal("detail-transaksi").show()');
    }

    public function bukaEditTransaksi($id)
    {
        $trx = Transaksi::findOrFail($id);
        
        $this->edit_trx_id = $trx->id;
        $this->edit_tanggal_bayar = $trx->tanggal_bayar;
        $this->edit_nominal = $trx->nominal_bayar ?? $trx->nominal; 
        $this->edit_keterangan = $trx->keterangan;
        $this->edit_bukti_lama = $trx->bukti_dokumen ?? $trx->lampiran ?? null;
        $this->edit_bukti_baru = null;
        $this->hapus_bukti_lama = false;

        $this->resetErrorBag();
        
        $this->js('$flux.modal("edit-transaksi").show()');
    }

    public function tandaiHapusBuktiLama()
    {
        $this->hapus_bukti_lama = true;
        $this->edit_bukti_lama = null;
    }

    public function updateTransaksi()
    {
        $this->validate([
            'edit_tanggal_bayar' => 'required|date',
            'edit_nominal'       => 'required|numeric|min:0',
            'edit_keterangan'    => 'nullable|string',
            'edit_bukti_baru'    => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $trx = Transaksi::findOrFail($this->edit_trx_id);
        
        $trx->tanggal_bayar = $this->edit_tanggal_bayar;
        $trx->keterangan = $this->edit_keterangan;

        if (array_key_exists('nominal_bayar', $trx->getAttributes())) {
            $trx->nominal_bayar = $this->edit_nominal;
        } else {
            $trx->nominal = $this->edit_nominal;
        }

        // Deteksi otomatis nama kolom dokumen di tabel transaksis
        $kolomDokumen = array_key_exists('lampiran', $trx->getAttributes()) ? 'lampiran' : 'bukti_dokumen';
        $pathLama = $trx->$kolomDokumen;

        // Jika dokumen lama ditandai untuk dihapus
        if ($this->hapus_bukti_lama && !$this->edit_bukti_baru && $pathLama) {
            Storage::disk('public')->delete($pathLama);
            $trx->$kolomDokumen = null;
        }

        // Jika ada dokumen baru yang diunggah
        if ($this->edit_bukti_baru) {
            if ($pathLama) {
                Storage::disk('public')->delete($pathLama);
            }
            $trx->$kolomDokumen = $this->edit_bukti_baru->store('bukti-transaksi', 'public');
        }

        $trx->save();

        $this->js('$flux.modal("edit-transaksi").close()');
        \Flux::toast('Data transaksi berhasil diperbarui!', variant: 'success');
        
        $this->reset([
            'edit_trx_id',
            'edit_tanggal_bayar',
            'edit_nominal',
            'edit_keterangan',
            'edit_bukti_lama',
            'edit_bukti_baru',
            'hapus_bukti_lama',
        ]);
    }

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
                                <th scope="col" class="px-4 py-3 font-semibold text-center rounded-tr-lg">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($this->transaksiDifilter as $index => $trx)
                                @php $adaFile = !empty($trx->bukti_dokumen ?? $trx->lampiran); @endphp
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors group">
                                    <td class="px-4 py-3.5 text-zinc-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3.5 font-semibold text-zinc-900 dark:text-zinc-200">
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ \Carbon\Carbon::create()->month($trx->periode_bulan)->locale('id')->isoFormat('MMMM') }}</span>
                                            @if($adaFile)
                                                <flux:icon.paper-clip class="w-3.5 h-3.5 text-indigo-500" title="Memiliki lampiran dokumen" />
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-zinc-500">
                                        {{ \Carbon\Carbon::parse($trx->tanggal_bayar)->locale('id')->isoFormat('D MMM YYYY') }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                        Rp {{ number_format($trx->nominal_bayar ?? $trx->nominal, 0, ',', '.') }}
                                    </td>
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
        @php
            $fileTrx = $trxDetail->bukti_dokumen ?? $trxDetail->lampiran ?? null;
            $isPdfTrx = $fileTrx && str_ends_with(strtolower($fileTrx), '.pdf');
        @endphp
        <div>
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-5 flex justify-between items-start">
                <div>
                    <flux:heading size="lg">Detail Pembayaran</flux:heading>
                    <flux:subheading>Periode {{ \Carbon\Carbon::create()->month($trxDetail->periode_bulan)->locale('id')->isoFormat('MMMM') }} {{ $trxDetail->periode_tahun }}</flux:subheading>
                </div>
                <flux:badge color="success" size="sm" class="mr-6">Berhasil</flux:badge>
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
                
                <!-- Bagian Dokumen / Bukti Pembayaran -->
                <div class="mt-4">
                    <span class="block text-xs text-zinc-500 mb-1.5">Dokumen / Bukti Pembayaran</span>
                    @if($fileTrx)
                        @if($isPdfTrx)
                            <a href="{{ asset('storage/' . $fileTrx) }}" target="_blank" class="flex items-center justify-between p-3 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50/70 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <flux:icon.document-text class="w-6 h-6 text-red-500 shrink-0" />
                                    <span class="text-sm font-medium truncate">{{ basename($fileTrx) }}</span>
                                </div>
                                <span class="text-xs font-semibold shrink-0 ml-2">Buka PDF ↗</span>
                            </a>
                        @else
                            <a href="{{ asset('storage/' . $fileTrx) }}" target="_blank" class="block group relative rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
                                <img src="{{ asset('storage/' . $fileTrx) }}" alt="Bukti Pembayaran" class="w-full max-h-56 object-contain bg-zinc-100 dark:bg-zinc-900">
                                <div class="py-2 px-3 text-center text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-zinc-100/90 dark:bg-zinc-800/90 border-t border-zinc-200 dark:border-zinc-700 group-hover:bg-indigo-50 dark:group-hover:bg-indigo-900/30 transition-colors">
                                    Klik untuk memperbesar gambar ↗
                                </div>
                            </a>
                        @endif
                    @else
                        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 text-center text-xs text-zinc-500">
                            Tidak ada dokumen / bukti yang dilampirkan.
                        </div>
                    @endif
                </div>
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
                <flux:subheading>Ubah tanggal pembayaran, nominal, catatan, atau dokumen bukti.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="edit_tanggal_bayar" type="date" label="Tanggal Pembayaran" required />
                
                <flux:input wire:model="edit_nominal" type="number" label="Nominal (Rp)" required />
                
                <flux:textarea wire:model="edit_keterangan" label="Catatan / Keterangan Transaksi" rows="3" />

                <!-- Input & Preview Dokumen / Bukti Pembayaran -->
                <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:input wire:model="edit_bukti_baru" type="file" label="Dokumen / Bukti Pembayaran (Opsional)" accept="image/*,application/pdf" />
                    <div class="text-[11px] text-zinc-500 mt-1">Format: JPG, PNG, WEBP, atau PDF. Maksimal ukuran: 5 MB.</div>

                    <div wire:loading wire:target="edit_bukti_baru" class="mt-2.5">
                        <div class="inline-flex items-center gap-2 text-xs text-indigo-600 dark:text-indigo-400 font-medium bg-indigo-50 dark:bg-indigo-500/10 px-3 py-1.5 rounded-md">
                            <flux:icon.arrow-path class="w-3.5 h-3.5 animate-spin" /> Sedang mengunggah file...
                        </div>
                    </div>

                    @error('edit_bukti_baru')
                        <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                    @enderror

                    <!-- Preview File Baru yang Baru Dipilih -->
                    @if($edit_bukti_baru)
                        <div class="mt-3 flex items-center justify-between p-2.5 rounded-lg border border-indigo-400 bg-indigo-50/70 dark:bg-indigo-900/20">
                            <a href="{{ $edit_bukti_baru->temporaryUrl() }}" target="_blank" class="flex items-center gap-2.5 min-w-0 group">
                                @if(strtolower($edit_bukti_baru->getClientOriginalExtension()) === 'pdf')
                                    <flux:icon.document-text class="w-7 h-7 text-red-500 shrink-0 group-hover:scale-105 transition-transform" />
                                @else
                                    <img src="{{ $edit_bukti_baru->temporaryUrl() }}" alt="Preview Baru" class="w-10 h-10 rounded object-cover shrink-0 border border-indigo-300">
                                @endif
                                <div class="min-w-0">
                                    <div class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 truncate">{{ $edit_bukti_baru->getClientOriginalName() }}</div>
                                    <div class="text-[11px] text-indigo-600/80 dark:text-indigo-400">File baru siap disimpan (Klik untuk pratinjau ↗)</div>
                                </div>
                            </a>
                            <flux:button type="button" wire:click="$set('edit_bukti_baru', null)" size="xs" variant="ghost" icon="x-mark" class="text-zinc-500 hover:text-red-500 shrink-0" title="Batalkan file baru" />
                        </div>

                    <!-- Tampilan Dokumen Lama dari Database -->
                    @elseif($edit_bukti_lama)
                        @php $isPdfLama = str_ends_with(strtolower($edit_bukti_lama), '.pdf'); @endphp
                        <div class="mt-3 flex items-center justify-between p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                            <a href="{{ asset('storage/' . $edit_bukti_lama) }}" target="_blank" class="flex items-center gap-2.5 min-w-0 group">
                                @if($isPdfLama)
                                    <flux:icon.document-text class="w-7 h-7 text-red-500 shrink-0 group-hover:scale-105 transition-transform" />
                                @else
                                    <img src="{{ asset('storage/' . $edit_bukti_lama) }}" alt="Dokumen Saat Ini" class="w-10 h-10 rounded object-cover shrink-0 border border-zinc-300 dark:border-zinc-600">
                                @endif
                                <div class="min-w-0">
                                    <div class="text-xs font-medium text-zinc-800 dark:text-zinc-200 truncate">{{ basename($edit_bukti_lama) }}</div>
                                    <div class="text-[11px] text-indigo-600 dark:text-indigo-400">Dokumen tersimpan (Klik untuk lihat ↗)</div>
                                </div>
                            </a>
                            <flux:button type="button" wire:click="tandaiHapusBuktiLama" size="xs" variant="ghost" icon="trash" class="text-red-500 hover:text-red-600 shrink-0" title="Hapus dokumen ini" />
                        </div>
                    @endif
                </div>
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