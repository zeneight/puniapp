<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

// Pastikan model-model baru ini sudah Bli buat ya!
use App\Models\Tamu;
use App\Models\KunjunganTamu;
use App\Models\RiwayatTindakLanjut;
use App\Models\Banjar;

new class extends Component
{
    use WithPagination;
    use WithFileUploads;

    #[Layout('layouts.app')]

    // Variabel Filter & Pencarian
    public $search = '';
    public $filter_tanggal = '';
    public $filter_status = '';
    public $filter_prioritas = '';

    // Variabel Master Tamu (Auto-fill)
    public $tamu_id = null;
    public $nama_pengunjung = '';
    public $kontak_wa = '';
    public $asal_instansi = '';
    public $pekerjaan_status = '';

    // Variabel Kunjungan
    public $tanggal_kunjungan;
    public $alasan_kunjungan;
    public $banjar_id = '';
    public $petugas = '';
    public $prioritas = 'Prioritas 3';

    // Variabel Detail & Tindak Lanjut Baru
    public $kunjungan_id;
    public $detailKunjungan;
    public $riwayat_kunjungan = [];
    public $tindak_lanjut_baru = '';
    public $status_baru = 'Proses';

    // Lokasi & File
    public $latitude = '-8.650000'; // Default: Tengah Kota Denpasar
    public $longitude = '115.216667';
    public $lampiran;

    public $edit_kunjungan_id;
    public $hapus_id;

    public function mount()
    {
        $this->tanggal_kunjungan = date('Y-m-d');
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterTanggal() { $this->resetPage(); }

    public function resetFilter()
    {
        $this->reset(['search', 'filter_tanggal', 'filter_status', 'filter_prioritas']);
        $this->resetPage();
    }

    public function batal()
    {
        $this->reset([
            'tamu_id', 'nama_pengunjung', 'kontak_wa', 'asal_instansi', 'pekerjaan_status',
            'alasan_kunjungan', 'banjar_id', 'petugas', 'tindak_lanjut_baru', 'kunjungan_id',
            'lampiran', 'edit_kunjungan_id'
        ]);
        $this->prioritas = 'Prioritas 3';
        $this->tanggal_kunjungan = date('Y-m-d');
        
        // Reset koordinat ke tengah Denpasar saat form dibatalkan
        $this->latitude = '-8.650000';
        $this->longitude = '115.216667';
        
        $this->resetValidation();
    }

    // --- FITUR AUTO FILL ---
    public function updatedNamaPengunjung($value)
    {
        $tamuExist = Tamu::where('nama_pengunjung', 'like', $value)->first();

        if ($tamuExist) {
            $this->tamu_id = $tamuExist->id;
            $this->kontak_wa = $tamuExist->kontak_wa;
            $this->asal_instansi = $tamuExist->asal_instansi;
            $this->pekerjaan_status = $tamuExist->pekerjaan_status;
        } else {
            $this->tamu_id = null; 
        }
    }

    // --- SIMPAN KUNJUNGAN BARU ---
    public function simpan()
    {
        $this->validate([
            'tanggal_kunjungan' => 'required|date',
            'nama_pengunjung' => 'required|string|max:255',
            'alasan_kunjungan' => 'required|string',
            'banjar_id' => 'required',
            'petugas' => 'required|string',
            'lampiran' => 'nullable|file|max:5120',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ]);

        // Proses upload lampiran (Berdasarkan Tahun/Bulan)
        $pathLampiran = null;
        if ($this->lampiran) {
            $folderPath = 'lampiran-kunjungan/' . date('Y/m');
            $pathLampiran = $this->lampiran->store($folderPath, 'public');
        }

        // 1. Simpan/Update Master Tamu
        if (!$this->tamu_id) {
            $tamu = Tamu::create([
                'nama_pengunjung' => $this->nama_pengunjung,
                'kontak_wa' => $this->kontak_wa,
                'asal_instansi' => $this->asal_instansi,
                'pekerjaan_status' => $this->pekerjaan_status,
            ]);
            $this->tamu_id = $tamu->id;
        }

        $kunjunganKe = KunjunganTamu::where('tamu_id', $this->tamu_id)->count() + 1;

        // 2. Simpan Transaksi Kunjungan
        $kunjungan = KunjunganTamu::create([
            'tamu_id' => $this->tamu_id,
            'user_id' => Auth::id(),
            'tanggal_kunjungan' => $this->tanggal_kunjungan,
            'banjar_id' => $this->banjar_id,
            'petugas' => $this->petugas,
            'alasan_kunjungan' => $this->alasan_kunjungan,
            'prioritas' => $this->prioritas,
            'status' => 'Tamu masuk',
            'kunjungan_ke' => $kunjunganKe,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'lampiran' => $pathLampiran,
        ]);

        // 3. Simpan Riwayat Awal
        RiwayatTindakLanjut::create([
            'kunjungan_id' => $kunjungan->id,
            'status_log' => 'Tamu masuk',
            'catatan' => 'Kunjungan baru didaftarkan.'
        ]);

        $this->js('$flux.modal("tambah-tamu").close()');
        $this->batal();
        \Flux::toast('Data kunjungan berhasil dicatat!', variant: 'success');
    }

    // --- BUKA MODAL DETAIL & TIMELINE ---
    public function bukaDetail($id)
    {
        $this->kunjungan_id = $id;
        $this->detailKunjungan = KunjunganTamu::with(['tamu', 'banjar'])->findOrFail($id);
        
        $this->riwayat_kunjungan = RiwayatTindakLanjut::where('kunjungan_id', $id)
                                        ->orderBy('created_at', 'asc')
                                        ->get();

        if ($this->detailKunjungan->status === 'Tamu masuk') {
            $this->status_baru = 'Proses';
        } else {
            $this->status_baru = $this->detailKunjungan->status;
        }
        
        $this->tindak_lanjut_baru = ''; 
        $this->resetValidation();

        $this->js('$flux.modal("detail-kunjungan").show()');
    }

    // --- SIMPAN TINDAK LANJUT DARI DALAM MODAL ---
    public function simpanTindakLanjut()
    {
        $this->validate([
            'tindak_lanjut_baru' => 'required|string',
            'status_baru' => 'required'
        ]);

        RiwayatTindakLanjut::create([
            'kunjungan_id' => $this->kunjungan_id,
            'status_log' => $this->status_baru,
            'catatan' => $this->tindak_lanjut_baru
        ]);

        $this->detailKunjungan->update(['status' => $this->status_baru]);

        $this->riwayat_kunjungan = RiwayatTindakLanjut::where('kunjungan_id', $this->kunjungan_id)
                                        ->orderBy('created_at', 'asc')
                                        ->get();
        $this->tindak_lanjut_baru = '';

        \Flux::toast('Tindak lanjut berhasil ditambahkan.', variant: 'success');
    }

    public function konfirmasiHapus($id)
    {
        $this->hapus_id = $id;
        $this->js('$flux.modal("hapus-tamu").show()');
    }

    public function destroy()
    {
        KunjunganTamu::findOrFail($this->hapus_id)->delete();
        $this->js('$flux.modal("hapus-tamu").close()');
        $this->reset('hapus_id');
        \Flux::toast('Data kunjungan dihapus.', variant: 'success');
    }

    // --- FUNGSI BUKA MODAL EDIT ---
    public function bukaEditKunjungan($id)
    {
        $this->js('$flux.modal("detail-kunjungan").close()');

        $kunjungan = KunjunganTamu::with('tamu')->findOrFail($id);
        
        $this->edit_kunjungan_id = $kunjungan->id;
        $this->tamu_id = $kunjungan->tamu_id;
        
        $this->nama_pengunjung = $kunjungan->tamu->nama_pengunjung;
        $this->kontak_wa = $kunjungan->tamu->kontak_wa;
        $this->pekerjaan_status = $kunjungan->tamu->pekerjaan_status;
        $this->asal_instansi = $kunjungan->tamu->asal_instansi;

        $this->tanggal_kunjungan = $kunjungan->tanggal_kunjungan;
        $this->banjar_id = $kunjungan->banjar_id;
        $this->petugas = $kunjungan->petugas;
        $this->alasan_kunjungan = $kunjungan->alasan_kunjungan;
        $this->prioritas = $kunjungan->prioritas;
        
        // PENTING: Ambil data lokasi lama agar map bergeser otomatis
        $this->latitude = $kunjungan->latitude ?? '-8.650000';
        $this->longitude = $kunjungan->longitude ?? '115.216667';

        $this->resetValidation();
        
        $this->js('setTimeout(() => { $flux.modal("edit-kunjungan").show() }, 300)');
    }

    // --- FUNGSI SIMPAN PERUBAHAN EDIT ---
    public function updateKunjungan()
    {
        $this->validate([
            'tanggal_kunjungan' => 'required|date',
            'nama_pengunjung' => 'required|string|max:255',
            'alasan_kunjungan' => 'required|string',
            'banjar_id' => 'required',
            'petugas' => 'required|string',
            'lampiran' => 'nullable|file|max:5120',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ]);

        $kunjungan = KunjunganTamu::findOrFail($this->edit_kunjungan_id);

        if ($kunjungan->tamu_id) {
            Tamu::where('id', $kunjungan->tamu_id)->update([
                'nama_pengunjung' => $this->nama_pengunjung,
                'kontak_wa' => $this->kontak_wa,
                'pekerjaan_status' => $this->pekerjaan_status,
                'asal_instansi' => $this->asal_instansi,
            ]);
        }

        $folderPath = 'lampiran-kunjungan/' . date('Y/m');

        $kunjungan->update([
            'tanggal_kunjungan' => $this->tanggal_kunjungan,
            'banjar_id' => $this->banjar_id,
            'petugas' => $this->petugas,
            'alasan_kunjungan' => $this->alasan_kunjungan,
            'prioritas' => $this->prioritas,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'lampiran' => $this->lampiran ? $this->lampiran->store($folderPath, 'public') : $kunjungan->lampiran,
        ]);

        $this->js('$flux.modal("edit-kunjungan").close()');
        $this->batal(); 
        \Flux::toast('Data utama kunjungan berhasil diperbarui!', variant: 'success');
    }

    public function with()
    {
        $totalKeseluruhan = KunjunganTamu::count();

        $query = KunjunganTamu::with(['tamu', 'banjar', 'user'])
            ->when($this->search, function ($q) {
                $q->whereHas('tamu', function ($sub) {
                    $sub->where('nama_pengunjung', 'like', '%' . $this->search . '%')
                        ->orWhere('asal_instansi', 'like', '%' . $this->search . '%');
                })->orWhere('alasan_kunjungan', 'like', '%' . $this->search . '%');
            })
            ->when($this->filter_tanggal, function ($q) {
                $q->whereDate('tanggal_kunjungan', $this->filter_tanggal);
            })
            ->orderBy('created_at', 'desc');
        
        if ($this->filter_status) $query->where('status', $this->filter_status);
        if ($this->filter_prioritas) $query->where('prioritas', $this->filter_prioritas);

        $totalDifilter = $query->count();

        return [
            'dataKunjungan' => $query->paginate(10),
            'daftarNamaTamu' => Tamu::pluck('nama_pengunjung'), 
            'daftarBanjar' => Banjar::orderBy('nama_banjar')->get(),
            'totalKeseluruhan' => $totalKeseluruhan,
            'totalDifilter' => $totalDifilter,
        ];
    }
};
?>
<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl">Buku Tamu & Pelayanan</flux:heading>
            <flux:subheading>Catat kunjungan, tamu, dan pantau riwayat pelayanannya.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" x-on:click="$flux.modal('tambah-tamu').show()">
            Catat Kunjungan Baru
        </flux:button>
    </div>

    <!-- Filter -->
    <div class="flex flex-col md:flex-row gap-3 mb-4">
        <flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="Cari nama, instansi, atau keperluan..." class="w-full md:w-96" />
        <flux:input wire:model.live="filter_tanggal" type="date" class="w-full md:w-48" />
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="filter_status" placeholder="Semua Status">
                <option value="">Semua Status</option>
                <option value="Tamu masuk">Tamu masuk</option>
                <option value="Proses">Proses</option>
                <option value="Selesai">Selesai</option>
            </flux:select>
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="filter_prioritas" placeholder="Semua Prioritas">
                <option value="">Semua Prioritas</option>
                <option value="Prioritas 1">Prioritas 1</option>
                <option value="Prioritas 2">Prioritas 2</option>
                <option value="Prioritas 3">Prioritas 3</option>
            </flux:select>
        </div>
        @if($search || $filter_tanggal || $filter_status || $filter_prioritas)
            <flux:button wire:click="resetFilter" variant="subtle" icon="x-mark" class="px-3">Reset</flux:button>
        @endif
    </div>

    <!-- TABEL UTAMA -->
    <flux:card class="relative">
        <div wire:loading wire:target="search, filter_tanggal, gotoPage" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
            <flux:icon.arrow-path class="w-6 h-6 animate-spin text-indigo-500" />
        </div>

        <div wire:loading.class="opacity-40" wire:target="search, filter_tanggal, gotoPage">
            <div class="mb-6 flex items-center gap-2 text-sm text-zinc-500 bg-zinc-50 dark:bg-zinc-800/50 p-2 rounded-lg border border-zinc-200 dark:border-zinc-700 w-fit">
                <flux:icon.chart-bar class="w-4 h-4" />
                <span>Menampilkan <strong class="text-zinc-900">{{ $totalDifilter }}</strong> data @if($search || $filter_status || $filter_prioritas)(dari total {{ $totalKeseluruhan }} data)@endif</span>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>No.</flux:table.column>
                    <flux:table.column>Info Tamu</flux:table.column>
                    <flux:table.column>Keperluan & Petugas</flux:table.column>
                    <flux:table.column>Prioritas</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($dataKunjungan as $index => $kunjungan)
                        <flux:table.row>
                            <flux:table.cell class="font-medium text-zinc-500">{{ $dataKunjungan->firstItem() + $index }}</flux:table.cell>
                            
                            <flux:table.cell>
                                <div class="font-semibold text-zinc-900 dark:text-white">{{ $kunjungan->tamu->nama_pengunjung ?? '-' }}</div>
                                <div class="text-xs text-zinc-500">Telp: {{ $kunjungan->tamu->kontak_wa ?? '-' }}</div>
                                <div class="text-xs text-zinc-500">Kunjungan ke-{{ $kunjungan->kunjungan_ke }}</div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="font-medium text-zinc-900 dark:text-white text-sm" title="{{ strip_tags($kunjungan->alasan_kunjungan) }}">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($kunjungan->alasan_kunjungan), 45) }}
                                </div>
                                <div class="text-xs text-zinc-500 mt-1">Petugas: {{ $kunjungan->petugas }} | {{ $kunjungan->banjar->nama_banjar ?? '-' }}</div>
                                <div class="text-[10px] text-zinc-400 mt-0.5">{{ \Carbon\Carbon::parse($kunjungan->tanggal_kunjungan)->translatedFormat('l, d F Y') }}</div>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <flux:badge size="sm" color="{{ $kunjungan->prioritas == 'Prioritas 1' ? 'red' : ($kunjungan->prioritas == 'Prioritas 2' ? 'yellow' : 'zinc') }}">{{ $kunjungan->prioritas }}</flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge size="sm" color="{{ $kunjungan->status == 'Selesai' ? 'green' : ($kunjungan->status == 'Proses' ? 'blue' : 'zinc') }}">{{ $kunjungan->status }}</flux:badge>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <flux:button wire:click="bukaDetail({{ $kunjungan->id }})" size="sm" variant="subtle" icon="clipboard-document-list">Detail</flux:button>
                                <a href="{{ route('buku-tamu.cetak', $kunjungan->tamu_id) }}" target="_blank">
                                    <flux:button size="sm" variant="ghost" icon="printer" title="Cetak Riwayat Kunjungan" class="text-zinc-600 hover:text-zinc-900" />
                                </a>
                                @if(Auth::user()->role === 'admin')
                                    <flux:button wire:click="konfirmasiHapus({{ $kunjungan->id }})" size="sm" variant="ghost" color="danger" icon="trash" class="ml-1" />
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="text-center text-zinc-500 py-8">Data kunjungan tidak ditemukan.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            <div class="mt-4">{{ $dataKunjungan->links() }}</div>
        </div>
    </flux:card>

    <!-- MODAL 1: TAMBAH DATA (Dengan Fitur Auto-fill Datalist) -->
    <flux:modal name="tambah-tamu" class="md:w-[700px]" wire:close="batal">
        <form wire:submit.prevent="simpan" class="space-y-5">
            <div>
                <flux:heading size="lg">Catat Kunjungan Baru</flux:heading>
                <flux:subheading>Ketik nama tamu lama untuk auto-fill, atau ketik nama baru.</flux:subheading>
            </div>

            <datalist id="listTamu">
                @foreach($daftarNamaTamu as $nama) <option value="{{ $nama }}"> @endforeach
            </datalist>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="tanggal_kunjungan" type="date" label="Tanggal Kunjungan" required />
                
                <flux:field>
                    <flux:label>Nama Pengunjung</flux:label>
                    <flux:input wire:model.live.debounce.400ms="nama_pengunjung" list="listTamu" placeholder="Ketik nama tamu..." required />
                    @if($tamu_id) <div class="text-[10px] text-green-500 font-semibold mt-1">✓ Tamu Dikenali (Auto-fill Aktif)</div> @endif
                </flux:field>
                
                <flux:input wire:model="kontak_wa" label="No Kontak WA" placeholder="Cth: 081234..." />
                <flux:input wire:model="pekerjaan_status" label="Pekerjaan / Jabatan" placeholder="Cth: Pegawai Negeri" />
                
                <div class="md:col-span-2">
                    <flux:input wire:model="asal_instansi" label="Instansi / Alamat Pengunjung (Asal Tamu)" placeholder="Cth: Universitas Udayana" />
                </div>

                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>File Lampiran / Foto (Opsional)</flux:label>
                        <input type="file" wire:model="lampiran" class="mt-2 block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400">
                        <div wire:loading wire:target="lampiran" class="text-xs text-indigo-500 mt-1">Mengunggah file...</div>
                        @error('lampiran') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </flux:field>
                </div>

                <flux:input wire:model="petugas" label="Petugas Penerima" placeholder="Nama petugas..." required />

                <div class="md:col-span-2">
                    <flux:textarea wire:model="alasan_kunjungan" label="Maksud Kunjungan / Laporan" rows="3" required />
                </div>
            </div>

            <flux:field>
                <flux:label>Tingkat Prioritas</flux:label>
                <flux:select wire:model="prioritas">
                    <option value="Prioritas 1">Prioritas 1 (Tinggi)</option>
                    <option value="Prioritas 2">Prioritas 2 (Sedang)</option>
                    <option value="Prioritas 3">Prioritas 3 (Rendah)</option>
                </flux:select>
            </flux:field>

            <div class="md:col-span-2 pt-3 border-t border-zinc-100 dark:border-zinc-800"></div>

            <flux:field>
                <flux:label>Lokasi Terkait (Wilayah Banjar)</flux:label>
                <flux:select wire:model="banjar_id" required>
                    <option value="">Pilih Banjar...</option>
                    @foreach($daftarBanjar as $b) <option value="{{ $b->id }}">{{ $b->nama_banjar }}</option> @endforeach
                </flux:select>
            </flux:field>

            <!-- PETA: Unik x-ref untuk Tambah -->
            <div class="md:col-span-2 pt-4" wire:ignore>
                <flux:heading size="sm" class="mb-3">Titik Koordinat Lokasi (Opsional)</flux:heading>
                <div x-data="{
                        map: null,
                        marker: null,
                        handlePaste(e) {
                            let pastedText = (e.clipboardData || window.clipboardData).getData('text');
                            if (pastedText.includes(',')) {
                                e.preventDefault();
                                let parts = pastedText.split(',');
                                let lat = parseFloat(parts[0].trim());
                                let lng = parseFloat(parts[1].trim());
                                if (!isNaN(lat) && !isNaN(lng)) {
                                    $wire.set('latitude', lat.toFixed(8));
                                    $wire.set('longitude', lng.toFixed(8));
                                    this.syncMap(lat, lng);
                                    e.target.value = lat.toFixed(8) + ', ' + lng.toFixed(8);
                                }
                            }
                        },
                        init() {
                            this.map = L.map($refs.mapContainerTambah, { scrollWheelZoom: false }).setView([-8.650000, 115.216667], 12);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(this.map);

                            const resizeObserver = new ResizeObserver(() => {
                                if (this.map) this.map.invalidateSize();
                            });
                            resizeObserver.observe(this.$refs.mapContainerTambah);

                            if ($wire.latitude && $wire.longitude) {
                                this.updateMarker($wire.latitude, $wire.longitude);
                                this.map.setView([$wire.latitude, $wire.longitude], 14);
                            }

                            this.map.on('click', (e) => {
                                const lat = e.latlng.lat.toFixed(8);
                                const lng = e.latlng.lng.toFixed(8);
                                this.updateMarker(lat, lng);
                                $wire.set('latitude', lat);
                                $wire.set('longitude', lng);
                            });

                            $watch('$wire.latitude', value => this.syncMap(value, $wire.longitude));
                        },
                        updateMarker(lat, lng) {
                            if (this.marker) {
                                this.marker.setLatLng([lat, lng]);
                            } else {
                                this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                                this.marker.on('dragend', (e) => {
                                    const position = this.marker.getLatLng();
                                    $wire.set('latitude', position.lat.toFixed(8));
                                    $wire.set('longitude', position.lng.toFixed(8));
                                });
                            }
                        },
                        syncMap(lat, lng) {
                            if (lat && lng) {
                                this.updateMarker(lat, lng);
                                this.map.setView([lat, lng], 16);
                            } else {
                                if (this.marker) {
                                    this.map.removeLayer(this.marker);
                                    this.marker = null;
                                }
                                this.map.setView([-8.650000, 115.216667], 12);
                            }
                        }
                    }" class="relative z-0">
                    
                    <div class="mb-4 p-3 bg-blue-50/50 dark:bg-blue-900/10 rounded-lg border border-blue-200 dark:border-blue-800/50">
                        <flux:field>
                            <flux:label class="text-blue-800 dark:text-blue-300 font-semibold mb-1">Cari dari Google Maps?</flux:label>
                            <flux:input x-on:paste="handlePaste($event)" icon="magnifying-glass" placeholder="Paste koordinat Google Maps (Contoh: -8.647961, 115.169800) di sini..." />
                        </flux:field>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <flux:input wire:model="latitude" label="Latitude" placeholder="Contoh: -8.650000" readonly />
                        <flux:input wire:model="longitude" label="Longitude" placeholder="Contoh: 115.216667" readonly />
                    </div>
                    <div class="text-[11px] text-zinc-500 mb-2">Klik atau geser pada peta untuk menentukan lokasi.</div>
                    
                    <!-- REFRENSINYA DIUBAH MENJADI mapContainerTambah -->
                    <div x-ref="mapContainerTambah" class="h-64 w-full rounded-lg shadow-sm border border-zinc-300 dark:border-zinc-700 z-0 relative"></div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Kunjungan</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- MODAL 2: DETAIL & TIMELINE -->
    <flux:modal name="detail-kunjungan" class="md:w-[650px]">
        @if($detailKunjungan)
        <div class="flex flex-col h-full max-h-[85vh]">
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-4 shrink-0">
                <div class="flex justify-between items-start">
                    <div>
                        <flux:heading size="lg">
                            {{ $detailKunjungan->tamu->nama_pengunjung }}
                            <flux:button wire:click="bukaEditKunjungan({{ $detailKunjungan->id }})" size="sm" variant="subtle" icon="pencil-square" class="ml-2 text-indigo-500" />
                        </flux:heading>
                        <flux:subheading>{{ $detailKunjungan->tamu->kontak_wa ?? '-' }} • {{ $detailKunjungan->tamu->pekerjaan_status ?? '-' }}</flux:subheading>
                    </div>
                    <flux:badge class="mr-8" color="{{ $detailKunjungan->status == 'Selesai' ? 'green' : 'blue' }}">{{ $detailKunjungan->status }}</flux:badge>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                    
                    <!-- KOLOM KIRI: Detail Maksud Kunjungan & Lampiran -->
                    <div class="bg-zinc-50 dark:bg-zinc-800/50 p-3.5 rounded-lg text-sm text-zinc-700 dark:text-zinc-300 flex flex-col justify-between">
                        <div>
                            <div class="font-semibold text-zinc-900 dark:text-white mb-1.5">Maksud Kunjungan:</div>
                            <div class="prose prose-sm dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 leading-relaxed mb-3">
                                {!! $detailKunjungan->alasan_kunjungan !!}
                            </div>
                            
                            @if($detailKunjungan->lampiran)
                            <div class="mt-3">
                                <a href="{{ asset('storage/' . $detailKunjungan->lampiran) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-indigo-50 dark:bg-indigo-900/30 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
                                    <flux:icon.paper-clip class="w-3.5 h-3.5" /> Lihat File Lampiran
                                </a>
                            </div>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-zinc-200 dark:border-zinc-700 flex flex-wrap items-center gap-2">
                            <!-- Lokasi Banjar -->
                            <flux:badge size="sm" color="zinc" icon="map-pin" class="!px-2">
                                {{ $detailKunjungan->banjar->nama_banjar ?? '-' }}
                            </flux:badge>
                            
                            <!-- Petugas -->
                            <flux:badge size="sm" color="zinc" icon="user" class="!px-2">
                                {{ $detailKunjungan->petugas }}
                            </flux:badge>
                            
                            <!-- Prioritas -->
                            <flux:badge size="sm" icon="flag" class="!px-2" color="{{ $detailKunjungan->prioritas == 'Prioritas 1' ? 'red' : ($detailKunjungan->prioritas == 'Prioritas 2' ? 'yellow' : 'zinc') }}">
                                {{ $detailKunjungan->prioritas }}
                            </flux:badge>
                        </div>
                    </div>

                    <!-- KOLOM KANAN: Peta Mini -->
                    @if($detailKunjungan->latitude && $detailKunjungan->longitude)
                    <div class="h-full rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700 shadow-sm flex flex-col relative z-0" wire:ignore>
                        <div x-data="{
                                map: null,
                                init() {
                                    this.map = L.map($refs.miniMap, {
                                        zoomControl: false,
                                        dragging: false,
                                        scrollWheelZoom: false,
                                        doubleClickZoom: false,
                                        touchZoom: false
                                    }).setView([{{ $detailKunjungan->latitude }}, {{ $detailKunjungan->longitude }}], 16);
                                    
                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
                                    L.marker([{{ $detailKunjungan->latitude }}, {{ $detailKunjungan->longitude }}]).addTo(this.map);
                                }
                            }" class="flex-1 min-h-[140px]">
                            <!-- Ketinggian map menggunakan h-full agar mengisi ruang sejajar dengan kotak teks -->
                            <div x-ref="miniMap" class="h-full w-full z-0 relative"></div>
                        </div>
                        
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $detailKunjungan->latitude }},{{ $detailKunjungan->longitude }}" target="_blank" class="block w-full shrink-0 text-center py-2 text-[11px] uppercase tracking-wider font-bold bg-zinc-100 dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 text-indigo-600 dark:text-indigo-400 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            Buka di Google Maps ↗
                        </a>
                    </div>
                    @else
                    <!-- Tampilan Cadangan Jika Tidak Ada Peta (Tetap menjaga bentuk kotak sebelahnya) -->
                    <div class="h-full rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 flex flex-col items-center justify-center p-4 text-center">
                        <flux:icon.map class="w-8 h-8 text-zinc-400 mb-2" />
                        <span class="text-xs text-zinc-500">Tidak ada titik koordinat lokasi yang dilampirkan.</span>
                    </div>
                    @endif

                </div>
            </div>

            <div class="flex-1 overflow-y-auto pr-2 mb-4">
                <div class="font-semibold text-sm mb-4">Timeline Penanganan</div>
                
                <div class="space-y-5 border-l-2 border-indigo-200 dark:border-indigo-900/50 ml-3">
                    @foreach($riwayat_kunjungan as $log)
                    <div class="relative pl-6">
                        <span class="absolute -left-[9px] top-1 w-4 h-4 rounded-full border-4 border-white dark:border-zinc-900 {{ $log->status_log == 'Selesai' ? 'bg-green-500' : ($log->status_log == 'Proses' ? 'bg-blue-500' : 'bg-zinc-400') }}"></span>
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-1">
                            <span class="font-bold text-sm text-zinc-900 dark:text-white">{{ $log->status_log }}</span>
                            <span class="text-[11px] text-zinc-500">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</span>
                        </div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-900 p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-800 shadow-sm mt-1">
                            {{ $log->catatan }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            @if($detailKunjungan->status !== 'Selesai')
            <div class="shrink-0 pt-4 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/30 -mx-6 -mb-6 px-6 pb-6 rounded-b-xl">
                <form wire:submit.prevent="simpanTindakLanjut" class="space-y-3">
                    <div class="font-semibold text-sm text-indigo-700 dark:text-indigo-400 mb-2">Catat Tindak Lanjut Baru</div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="w-full sm:w-1/3">
                            <flux:select wire:model="status_baru" required>
                                <option value="Proses">Proses</option>
                                <option value="Selesai">Selesai (Tutup Laporan)</option>
                            </flux:select>
                        </div>
                        <div class="w-full sm:w-2/3">
                            <flux:input wire:model="tindak_lanjut_baru" placeholder="Ketik hasil tindakan yang dilakukan..." required />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-3">
                        <flux:button type="button" x-on:click="$flux.modal('detail-kunjungan').close()" variant="ghost" size="sm">Tutup</flux:button>
                        <flux:button type="submit" variant="primary" size="sm" icon="paper-airplane">Kirim Tindakan</flux:button>
                    </div>
                </form>
            </div>
            @else
            <div class="shrink-0 pt-4 border-t border-zinc-200 flex justify-end">
                 <flux:button type="button" x-on:click="$flux.modal('detail-kunjungan').close()" variant="primary">Tutup Window</flux:button>
            </div>
            @endif
        </div>
        @endif
    </flux:modal>

    <!-- MODAL 3: EDIT DATA UTAMA KUNJUNGAN -->
    <flux:modal name="edit-kunjungan" class="md:w-[700px]" wire:close="batal">
        <form wire:submit.prevent="updateKunjungan" class="space-y-5">
            <div>
                <flux:heading size="lg">Edit Data Induk Kunjungan</flux:heading>
                <flux:subheading>Perbaiki kesalahan ketik awal (typo) pada identitas tamu atau keperluan.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="tanggal_kunjungan" type="date" label="Tanggal Kunjungan" required />
                <flux:input wire:model="nama_pengunjung" label="Nama Pengunjung" required />
                <flux:input wire:model="kontak_wa" label="No Kontak WA" />
                <flux:input wire:model="pekerjaan_status" label="Pekerjaan / Jabatan" />
                
                <div class="md:col-span-2">
                    <flux:input wire:model="asal_instansi" label="Instansi / Alamat Pengunjung (Asal Tamu)" />
                </div>

                <div class="md:col-span-2">
                    <flux:label>File Lampiran / Foto (Opsional)</flux:label>
                    <input type="file" wire:model="lampiran" class="mt-2 block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/30 dark:file:text-indigo-400">
                    <div wire:loading wire:target="lampiran" class="text-xs text-indigo-500 mt-1">Mengunggah file...</div>
                    @error('lampiran') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <flux:input wire:model="petugas" label="Petugas Penerima" required />

                <div class="md:col-span-2">
                    <flux:textarea wire:model="alasan_kunjungan" label="Maksud Kunjungan / Laporan" rows="3" required />
                </div>
            </div>

            <flux:field>
                <flux:label>Tingkat Prioritas</flux:label>
                <flux:select wire:model="prioritas">
                    <option value="Prioritas 1">Prioritas 1 (Tinggi)</option>
                    <option value="Prioritas 2">Prioritas 2 (Sedang)</option>
                    <option value="Prioritas 3">Prioritas 3 (Rendah)</option>
                </flux:select>
            </flux:field>

            <div class="md:col-span-2 pt-3 border-t border-zinc-100 dark:border-zinc-800"></div>

            <flux:field>
                <flux:label>Lokasi Terkait (Wilayah Banjar)</flux:label>
                <flux:select wire:model="banjar_id" required>
                    <option value="">Pilih Banjar...</option>
                    @foreach($daftarBanjar as $b) <option value="{{ $b->id }}">{{ $b->nama_banjar }}</option> @endforeach
                </flux:select>
            </flux:field>

            <!-- PETA: Unik x-ref untuk Edit -->
            <div class="md:col-span-2 pt-4" wire:ignore>
                <flux:heading size="sm" class="mb-3">Titik Koordinat Lokasi (Opsional)</flux:heading>
                <div x-data="{
                        map: null,
                        marker: null,
                        handlePaste(e) {
                            let pastedText = (e.clipboardData || window.clipboardData).getData('text');
                            if (pastedText.includes(',')) {
                                e.preventDefault();
                                let parts = pastedText.split(',');
                                let lat = parseFloat(parts[0].trim());
                                let lng = parseFloat(parts[1].trim());
                                if (!isNaN(lat) && !isNaN(lng)) {
                                    $wire.set('latitude', lat.toFixed(8));
                                    $wire.set('longitude', lng.toFixed(8));
                                    this.syncMap(lat, lng);
                                    e.target.value = lat.toFixed(8) + ', ' + lng.toFixed(8);
                                }
                            }
                        },
                        init() {
                            this.map = L.map($refs.mapContainerEdit, { scrollWheelZoom: false }).setView([-8.650000, 115.216667], 12);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(this.map);

                            const resizeObserver = new ResizeObserver(() => {
                                if (this.map) this.map.invalidateSize();
                            });
                            resizeObserver.observe(this.$refs.mapContainerEdit);

                            if ($wire.latitude && $wire.longitude) {
                                this.updateMarker($wire.latitude, $wire.longitude);
                                this.map.setView([$wire.latitude, $wire.longitude], 14);
                            }

                            this.map.on('click', (e) => {
                                const lat = e.latlng.lat.toFixed(8);
                                const lng = e.latlng.lng.toFixed(8);
                                this.updateMarker(lat, lng);
                                $wire.set('latitude', lat);
                                $wire.set('longitude', lng);
                            });

                            $watch('$wire.latitude', value => this.syncMap(value, $wire.longitude));
                        },
                        updateMarker(lat, lng) {
                            if (this.marker) {
                                this.marker.setLatLng([lat, lng]);
                            } else {
                                this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                                this.marker.on('dragend', (e) => {
                                    const position = this.marker.getLatLng();
                                    $wire.set('latitude', position.lat.toFixed(8));
                                    $wire.set('longitude', position.lng.toFixed(8));
                                });
                            }
                        },
                        syncMap(lat, lng) {
                            if (lat && lng) {
                                this.updateMarker(lat, lng);
                                this.map.setView([lat, lng], 16);
                            } else {
                                if (this.marker) {
                                    this.map.removeLayer(this.marker);
                                    this.marker = null;
                                }
                                this.map.setView([-8.650000, 115.216667], 12);
                            }
                        }
                    }" class="relative z-0">
                    
                    <div class="mb-4 p-3 bg-blue-50/50 dark:bg-blue-900/10 rounded-lg border border-blue-200 dark:border-blue-800/50">
                        <flux:field>
                            <flux:label class="text-blue-800 dark:text-blue-300 font-semibold mb-1">Cari dari Google Maps?</flux:label>
                            <flux:input x-on:paste="handlePaste($event)" icon="magnifying-glass" placeholder="Paste koordinat Google Maps di sini..." />
                        </flux:field>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <flux:input wire:model="latitude" label="Latitude" readonly />
                        <flux:input wire:model="longitude" label="Longitude" readonly />
                    </div>
                    <div class="text-[11px] text-zinc-500 mb-2">Klik atau geser pada peta untuk menentukan lokasi presisi.</div>
                    
                    <!-- REFRENSINYA DIUBAH MENJADI mapContainerEdit -->
                    <div x-ref="mapContainerEdit" class="h-64 w-full rounded-lg shadow-sm border border-zinc-300 dark:border-zinc-700 z-0 relative"></div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal Hapus -->
    <flux:modal name="hapus-tamu" class="min-w-[400px]">
        <div class="mb-4">
            <flux:heading size="lg">Hapus Kunjungan?</flux:heading>
            <flux:subheading>Tindakan ini juga akan menghapus seluruh riwayat pelacakan.</flux:subheading>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
            <flux:button wire:click="destroy" variant="danger">Ya, Hapus</flux:button>
        </div>
    </flux:modal>
  
    <!-- Library Peta Leaflet (Cukup link library saja, script logika sudah pakai Alpine JS di atas) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</div>