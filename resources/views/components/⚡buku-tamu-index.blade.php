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
    public $latitude = null;
    public $longitude = null;
    public $lampiran;

    public $edit_kunjungan_id;
    public $hapus_id;

    // text
    public $edit_riwayat_id = null;
    public $edit_catatan = '';
    public $edit_status_log = '';

    // State penampung data rombongan
    public $rombonganTamu = [];

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

    // Saat modal tambah kunjungan dibuka, sediakan 1 baris form kosong
    public function bukaTambahKunjungan()
    {
        $this->reset();
        $this->rombonganTamu = [
            ['nama' => '', 'wa' => '', 'pekerjaan' => '']
        ];
        $this->js('$flux.modal("modal-kunjungan").show()');
    }

    // Fungsi untuk menambah baris form tamu baru
    public function tambahTamu()
    {
        $this->rombonganTamu[] = ['nama' => '', 'wa' => '', 'pekerjaan' => ''];
    }

    // Fungsi untuk menghapus baris form
    public function hapusTamu($index)
    {
        unset($this->rombonganTamu[$index]);
        $this->rombonganTamu = array_values($this->rombonganTamu); // Susun ulang index array
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

        // PENTING: Kembalikan rombongan ke 1 baris kosong
        $this->rombonganTamu = [
            ['nama' => '', 'wa' => '', 'pekerjaan' => '', 'tamu_id' => null]
        ];
        
        // Reset koordinat ke tengah Denpasar saat form dibatalkan
        $this->latitude = null;
        $this->longitude = null;

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

    public function updated($property, $value)
    {
        // Mengecek apakah field yang berubah adalah nama di dalam array rombonganTamu
        // Contoh property yang ditangkap: "rombonganTamu.0.nama"
        if (str_starts_with($property, 'rombonganTamu.') && str_ends_with($property, '.nama')) {
            
            // Ambil nomor index-nya (misal dari "rombonganTamu.0.nama" kita ambil angka 0)
            $parts = explode('.', $property);
            $index = $parts[1];

            // Cari tamu di database (Sesuaikan 'Tamu' dengan nama Model Bli yang asli)
            $tamu = Tamu::where('nama_pengunjung', $value)->first();

            if ($tamu) {
                // Jika tamu ditemukan, auto-fill WA, Pekerjaan, dan simpan ID-nya
                $this->rombonganTamu[$index]['wa'] = $tamu->kontak_wa;
                $this->rombonganTamu[$index]['pekerjaan'] = $tamu->pekerjaan_status;
                $this->rombonganTamu[$index]['tamu_id'] = $tamu->id; // Penting untuk UI "Tamu Dikenali"
            } else {
                // Jika nama diganti jadi tamu baru, reset ID-nya agar label "Tamu Dikenali" hilang
                $this->rombonganTamu[$index]['tamu_id'] = null;
            }
        }
    }

    // --- SIMPAN KUNJUNGAN BARU ---
    public function simpan()
    {
        // 1. VALIDASI BARU 
        $this->validate([
            'tanggal_kunjungan' => 'required|date',
            'rombonganTamu' => 'required|array|min:1',
            'rombonganTamu.*.nama' => 'required|string|max:255',
            'alasan_kunjungan' => 'required|string',
            'banjar_id' => 'required',
            'petugas' => 'required|string',
            'lampiran' => 'nullable|file|max:5120',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ]);

        // 2. Proses upload lampiran
        $pathLampiran = null;
        if ($this->lampiran) {
            $folderPath = 'lampiran-kunjungan/' . date('Y/m');
            $pathLampiran = $this->lampiran->store($folderPath, 'public');
        }

        // 3. Simpan/Cek semua tamu TERLEBIH DAHULU agar kita dapat ID-nya
        $tamuIds = [];
        foreach ($this->rombonganTamu as $tamuForm) {
            if (!empty($tamuForm['nama'])) {
                $tamu = Tamu::firstOrCreate(
                    ['nama_pengunjung' => $tamuForm['nama']],
                    [
                        'kontak_wa' => $tamuForm['wa'] ?? null,
                        'pekerjaan_status' => $tamuForm['pekerjaan'] ?? null
                    ]
                );
                $tamuIds[] = $tamu->id;
            }
        }

        // 4. Menghitung Kunjungan Ke-berapa (Berdasarkan Tamu Pertama / Ketua)
        $kunjunganKe = 1;
        if (isset($tamuIds[0])) {
            $kunjunganKe = KunjunganTamu::where('tamu_id', $tamuIds[0])->count() + 1;
        }

        // 5. Simpan Transaksi Kunjungan Induk
        $kunjungan = KunjunganTamu::create([
            'tamu_id' => $tamuIds[0] ?? null, // <-- Trick-nya di sini: Masukkan ID Ketua Rombongan
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
            'asal_instansi' => $this->asal_instansi,
        ]);

        // 6. Pasangkan semua anggota rombongan ke tabel pivot (Many-to-Many)
        $kunjungan->tamu()->attach($tamuIds);

        // 7. Simpan Riwayat Awal
        RiwayatTindakLanjut::create([
            'kunjungan_id' => $kunjungan->id,
            'status_log' => 'Tamu masuk',
            'catatan' => 'Kunjungan baru didaftarkan.'
        ]);

        // Bersihkan state dan tutup modal
        $this->batal();
        session()->flash('success', 'Data kunjungan berhasil dicatat!');
        return redirect()->to(request()->header('Referer'));
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

        $this->js("setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
        }, 200)");
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
        
        $this->rombonganTamu = [];
        foreach ($kunjungan->tamu as $t) {
            $this->rombonganTamu[] = [
                'nama' => $t->nama_pengunjung,
                'wa' => $t->kontak_wa,
                'pekerjaan' => $t->pekerjaan_status,
                'tamu_id' => $t->id, 
            ];
        }
        
        if (empty($this->rombonganTamu)) {
            $this->rombonganTamu = [['nama' => '', 'wa' => '', 'pekerjaan' => '', 'tamu_id' => null]];
        }

        // --- PERUBAHAN DI SINI ---
        // Ubah dari: $this->asal_instansi = $kunjungan->tamu->asal_instansi; 
        // Menjadi langsung ambil dari objek kunjungan:
        $this->asal_instansi = $kunjungan->asal_instansi; 
        // -------------------------

        $this->tanggal_kunjungan = $kunjungan->tanggal_kunjungan;
        $this->banjar_id = $kunjungan->banjar_id;
        $this->petugas = $kunjungan->petugas;
        $this->alasan_kunjungan = $kunjungan->alasan_kunjungan;
        $this->prioritas = $kunjungan->prioritas;
        
        $this->latitude = $kunjungan->latitude;
        $this->longitude = $kunjungan->longitude;

        $this->resetValidation();
        
        $this->js('setTimeout(() => { $flux.modal("edit-kunjungan").show() }, 300)');
    }

    // --- FUNGSI UPDATE DATA KUNJUNGAN ---
    public function updateKunjungan()
    {
        $this->validate([
            'tanggal_kunjungan' => 'required|date',
            'rombonganTamu' => 'required|array|min:1',
            'rombonganTamu.*.nama' => 'required|string|max:255',
            'alasan_kunjungan' => 'required|string',
            'banjar_id' => 'required',
            'petugas' => 'required|string',
            'lampiran' => 'nullable|file|max:5120',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ]);

        $kunjungan = KunjunganTamu::findOrFail($this->edit_kunjungan_id);

        // 1. Simpan/Cek semua tamu untuk di-sync
        $tamuIds = [];
        foreach ($this->rombonganTamu as $tamuForm) {
            if (!empty($tamuForm['nama'])) {
                $tamu = Tamu::updateOrCreate(
                    ['nama_pengunjung' => $tamuForm['nama']],
                    [
                        'kontak_wa' => $tamuForm['wa'] ?? null,
                        'pekerjaan_status' => $tamuForm['pekerjaan'] ?? null
                    ]
                );
                $tamuIds[] = $tamu->id;
            }
        }

        // 2. Proses upload lampiran baru (jika ada)
        $folderPath = 'lampiran-kunjungan/' . date('Y/m');
        $pathLampiran = $this->lampiran ? $this->lampiran->store($folderPath, 'public') : $kunjungan->lampiran;

        // 3. Update Data Induk Kunjungan
        $kunjungan->update([
            'tamu_id' => $tamuIds[0] ?? null, // Trick mempertahankan ketua rombongan (jaga DB)
            'tanggal_kunjungan' => $this->tanggal_kunjungan,
            'banjar_id' => $this->banjar_id,
            'petugas' => $this->petugas,
            'alasan_kunjungan' => $this->alasan_kunjungan,
            'prioritas' => $this->prioritas,
            'asal_instansi' => $this->asal_instansi,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'lampiran' => $pathLampiran,
        ]);

        // 4. Perbarui Relasi Pivot (Singkirkan tamu lama yang dihapus, masukkan tamu baru)
        $kunjungan->tamu()->sync($tamuIds);

        // 5. Update data di memori agar modal detail otomatis ter-refresh saat kembali
        $this->detailKunjungan = $kunjungan->fresh(['tamu', 'banjar']);

        // 6. Tutup Modal & Reset
        $this->js('$flux.modal("edit-kunjungan").close()');
        $this->batal(); 
        
        // Re-open detail modal
        $this->js('setTimeout(() => { $flux.modal("detail-kunjungan").show() }, 400)');
        \Flux::toast('Data utama kunjungan berhasil diperbarui!', variant: 'success');
    }

    // Fungsi untuk membuka form edit riwayat di dalam modal
    public function editRiwayat($riwayat_id)
    {
        $riwayat = RiwayatTindakLanjut::findOrFail($riwayat_id);
        $this->edit_riwayat_id = $riwayat->id;
        $this->edit_catatan = $riwayat->catatan;
        $this->edit_status_log = $riwayat->status_log;
    }

    // Fungsi batal edit
    public function batalEditRiwayat()
    {
        $this->reset(['edit_riwayat_id', 'edit_catatan', 'edit_status_log']);
    }

    // Fungsi simpan perubahan riwayat
    public function updateRiwayat()
    {
        $this->validate([
            'edit_catatan' => 'required',
            'edit_status_log' => 'required',
        ]);

        $riwayat = RiwayatTindakLanjut::findOrFail($this->edit_riwayat_id);
        $riwayat->update([
            'catatan' => $this->edit_catatan,
            'status_log' => $this->edit_status_log,
        ]);

        // PERBAIKAN: Refresh langsung variabel $riwayat_kunjungan yang dipakai di looping Blade
        $this->riwayat_kunjungan = RiwayatTindakLanjut::where('kunjungan_id', $riwayat->kunjungan_id)
                                    ->orderBy('created_at', 'asc') // Sesuaikan urutannya 
                                    ->get();

        $this->batalEditRiwayat();
        \Flux::toast('Riwayat berhasil diperbarui!', variant: 'success');
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
                    <flux:table.column>Asal Instansi</flux:table.column>
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
                                <div class="font-semibold text-zinc-900 dark:text-white">{{ $kunjungan->tamu->pluck('nama_pengunjung')->join(', ') }}</div>
                                <div class="text-xs text-zinc-500">Telp: {{ $kunjungan->tamu->pluck('kontak_wa')->join(', ') ?? '-' }}</div>
                                <div class="text-xs text-zinc-500">Kunjungan ke-{{ $kunjungan->kunjungan_ke }}</div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="font-medium text-zinc-900 dark:text-white text-sm">{{ $kunjungan->asal_instansi ?? '-' }}</div>
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
                <div class="md:col-span-2 flex flex-col md:flex-row gap-4">
                    <flux:input wire:model="tanggal_kunjungan" type="date" label="Tanggal Kunjungan" class="w-full md:w-1/2" required />
                </div>

                <!-- AREA ROMBONGAN TAMU (REPEATER) -->
                <div class="md:col-span-2">
                    <div class="flex justify-between items-center mb-2">
                        <flux:label class="text-indigo-600 font-semibold">Daftar Pengunjung</flux:label>
                        <flux:button wire:click="tambahTamu" size="sm" variant="outline" icon="plus">Tambah Orang</flux:button>
                    </div>

                    <div class="space-y-3">
                        @foreach($rombonganTamu as $index => $tamu)
                        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 rounded-lg relative" wire:key="tamu-{{ $index }}">
                            
                            <!-- Tombol Hapus Baris -->
                            @if(count($rombonganTamu) > 1)
                            <button type="button" wire:click="hapusTamu({{ $index }})" class="absolute top-2 right-2 text-red-500 hover:text-red-700 p-1 bg-white dark:bg-zinc-900 rounded shadow-sm">
                                <flux:icon.trash class="w-4 h-4" />
                            </button>
                            @endif
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pr-8">
                                <flux:field>
                                    <flux:label>Nama Tamu {{ $index + 1 }}</flux:label>
                                    <flux:input wire:model.live.debounce.400ms="rombonganTamu.{{ $index }}.nama" list="listTamu" placeholder="Ketik nama tamu..." required />
                                    
                                    <!-- Ubah pengecekan labelnya menjadi seperti ini -->
                                    @if(!empty($rombonganTamu[$index]['tamu_id'])) 
                                        <div class="text-[10px] text-green-500 font-semibold mt-1">✓ Tamu Dikenali (Auto-fill Aktif)</div> 
                                    @endif
                                </flux:field>
                                
                                <flux:input wire:model="rombonganTamu.{{ $index }}.wa" label="No Kontak WA" placeholder="Cth: 081234..." />
                                <flux:input wire:model="rombonganTamu.{{ $index }}.pekerjaan" label="Pekerjaan / Jabatan" placeholder="Cth: Instruktur" />
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <!-- END AREA ROMBONGAN -->

                <div class="md:col-span-2 mt-2">
                    <flux:input wire:model="asal_instansi" label="Instansi / Alamat Pengunjung (Asal Tamu)" placeholder="Cth: Universitas Udayana" />
                </div>
                <!-- ... lampiran dan sisanya biarkan sama ... -->

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
                    <flux:label class="mb-2">Maksud Kunjungan / Laporan</flux:label>
                    
                    <!-- Wrapper dengan wire:ignore agar Livewire tidak merusak editor saat me-refresh komponen -->
                    <div wire:ignore class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-md">
                        <div x-data="{
                            content: @entangle('alasan_kunjungan'),
                            init() {
                                let quill = new Quill(this.$refs.editor, {
                                    theme: 'snow',
                                    modules: {
                                        toolbar: [
                                            ['bold', 'italic', 'underline'],
                                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                            ['clean'] // Tombol hapus format
                                        ]
                                    }
                                });
                                
                                // Isi nilai awal jika mode edit
                                if (this.content) { quill.root.innerHTML = this.content; }
                                
                                // Kirim perubahan ke Livewire
                                quill.on('text-change', () => {
                                    this.content = quill.root.innerHTML;
                                });
                                
                                // Dengarkan perubahan dari Livewire (saat tombol batal/reset ditekan)
                                this.$watch('content', value => {
                                    if (value !== quill.root.innerHTML) {
                                        quill.root.innerHTML = value || '';
                                    }
                                });
                            }
                        }">
                            <div x-ref="editor" class="min-h-[120px] text-zinc-800 dark:text-zinc-200"></div>
                        </div>
                    </div>
                    @error('alasan_kunjungan') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
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
                            {{ $detailKunjungan->tamu->pluck('nama_pengunjung')->join(', ') }}
                            <flux:button wire:click="bukaEditKunjungan({{ $detailKunjungan->id }})" size="sm" variant="subtle" icon="pencil-square" class="ml-2 text-indigo-500" />
                        </flux:heading>
                        <flux:subheading>
                            Asal Instansi: <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $detailKunjungan->asal_instansi ?? '-' }}</span>
                            <br>WA: {{ $detailKunjungan->tamu->pluck('kontak_wa')->filter()->join(', ') ?: '-' }}
                            <br>Pekerjaan: {{ $detailKunjungan->tamu->pluck('pekerjaan_status')->filter()->unique()->join(', ') ?: '-' }}
                        </flux:subheading>
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
                    <div class="h-full rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700 shadow-sm flex flex-col relative z-0" wire:key="map-kunjungan-{{ $detailKunjungan->id }}">
                        <div x-data="{
                                map: null,
                                marker: null,
                                lat: {{ $detailKunjungan->latitude }},
                                lng: {{ $detailKunjungan->longitude }},
                                init() {
                                    this.initMap();
                                },
                                initMap() {
                                    if (this.map) {
                                        this.map.remove();
                                        this.map = null;
                                    }

                                    this.map = L.map(this.$refs.miniMap, {
                                        zoomControl: false,
                                        dragging: false,
                                        scrollWheelZoom: false,
                                        doubleClickZoom: false,
                                        touchZoom: false
                                    }).setView([this.lat, this.lng], 16);
                                    
                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
                                    this.marker = L.marker([this.lat, this.lng]).addTo(this.map);

                                    setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 150);
                                }
                            }" class="flex-1 min-h-[140px]" wire:ignore>
                            
                            <div x-ref="miniMap" class="h-full w-full z-0 relative"></div>
                        </div>
                        
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $detailKunjungan->latitude }},{{ $detailKunjungan->longitude }}" target="_blank" class="block w-full shrink-0 text-center py-2 text-[11px] uppercase tracking-wider font-bold bg-zinc-100 dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 text-indigo-600 dark:text-indigo-400 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            Buka di Google Maps ↗
                        </a>
                    </div>
                    @else
                    <div class="h-full rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700 flex flex-col items-center justify-center p-4 text-center" wire:key="map-empty-{{ $detailKunjungan->id }}">
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
                        <!-- Titik Indikator Timeline -->
                        <span class="absolute -left-[9px] top-1 w-4 h-4 rounded-full border-4 border-white dark:border-zinc-900 {{ $log->status_log == 'Selesai' ? 'bg-green-500' : ($log->status_log == 'Proses' ? 'bg-blue-500' : 'bg-zinc-400') }}"></span>
                        
                        <!-- JIKA SEDANG MODE EDIT UNTUK LOG INI -->
                        @if($edit_riwayat_id == $log->id)
                            <div class="bg-indigo-50/50 dark:bg-indigo-900/10 p-3 rounded-lg border border-indigo-200 dark:border-indigo-800/50 shadow-sm mt-1">
                                
                                <flux:field class="mb-3">
                                    <flux:label>Ubah Status</flux:label>
                                    <flux:select wire:model="edit_status_log" size="sm">
                                        <option value="Tamu masuk">Tamu masuk</option>
                                        <option value="Proses">Proses</option>
                                        <option value="Selesai">Selesai</option>
                                    </flux:select>
                                </flux:field>

                                <flux:field class="mb-3">
                                    <flux:label class="mb-2">Perbaiki Catatan</flux:label>
                                    <!-- WYSIWYG Editor Quill -->
                                    <div wire:ignore class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-md">
                                        <div x-data="{
                                            content: @entangle('edit_catatan'),
                                            init() {
                                                let quill = new Quill(this.$refs.editorRiwayat, {
                                                    theme: 'snow',
                                                    modules: { toolbar: [['bold', 'italic', 'underline'], [{'list': 'ordered'}, {'list': 'bullet'}], ['clean']] }
                                                });
                                                if (this.content) { quill.root.innerHTML = this.content; }
                                                quill.on('text-change', () => { this.content = quill.root.innerHTML; });
                                                this.$watch('content', value => { if (value !== quill.root.innerHTML) { quill.root.innerHTML = value || ''; } });
                                            }
                                        }">
                                            <div x-ref="editorRiwayat" class="text-sm text-zinc-800 dark:text-zinc-200"></div>
                                        </div>
                                    </div>
                                </flux:field>

                                <div class="flex gap-2 justify-end">
                                    <flux:button wire:click="batalEditRiwayat" size="sm" variant="ghost">Batal</flux:button>
                                    <flux:button wire:click="updateRiwayat" size="sm" variant="primary">Simpan</flux:button>
                                </div>
                            </div>

                        <!-- JIKA MODE TAMPILAN BIASA -->
                        @else
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-1 group">
                                <span class="font-bold text-sm text-zinc-900 dark:text-white">{{ $log->status_log }}</span>
                                
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] text-zinc-500">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</span>
                                    <!-- Tombol Edit Riwayat (Hanya muncul jika di-hover untuk menjaga UI tetap bersih) -->
                                    <button type="button" wire:click="editRiwayat({{ $log->id }})" class="text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        <flux:icon.pencil-square class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                            
                            <div class="text-sm text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-900 p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-800 shadow-sm mt-1">
                                
                                <div class="[&>p]:mb-1 [&>ol]:list-decimal [&>ol]:ml-5 [&>ul]:list-disc [&>ul]:ml-5 last:[&>*]:mb-0">
                                    {!! $log->catatan !!}
                                </div>
                            </div>
                        @endif

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
                            <!-- Wrapper dengan wire:ignore agar Livewire tidak me-reset UI editor -->
                            <div wire:ignore class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-md overflow-hidden shadow-sm">
                                <div x-data="{
                                    content: @entangle('tindak_lanjut_baru'),
                                    init() {
                                        let quill = new Quill(this.$refs.editorTindakLanjut, {
                                            theme: 'snow',
                                            // Placeholder diletakkan di dalam konfigurasi Quill
                                            placeholder: 'Ketik hasil tindakan yang dilakukan...',
                                            modules: {
                                                toolbar: [
                                                    ['bold', 'italic', 'underline'],
                                                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                                    ['clean']
                                                ]
                                            }
                                        });
                                        
                                        if (this.content) { quill.root.innerHTML = this.content; }
                                        
                                        quill.on('text-change', () => {
                                            this.content = quill.root.innerHTML;
                                        });
                                        
                                        // Mengosongkan editor otomatis ketika berhasil disimpan (variabel di-reset)
                                        this.$watch('content', value => {
                                            if (value !== quill.root.innerHTML) {
                                                quill.root.innerHTML = value || '';
                                            }
                                        });
                                    }
                                }">
                                    <!-- Tempat editor dirender -->
                                    <div x-ref="editorTindakLanjut" class="text-sm text-zinc-800 dark:text-zinc-200"></div>
                                </div>
                            </div>
                            
                            <!-- Pesan error validasi manual (karena kita tidak pakai flux:input lagi) -->
                            @error('tindak_lanjut_baru') 
                                <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> 
                            @enderror
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
                <div class="md:col-span-2 flex flex-col md:flex-row gap-4">
                    <flux:input wire:model="tanggal_kunjungan" type="date" label="Tanggal Kunjungan" class="w-full md:w-1/2" required />
                </div>

                <!-- AREA ROMBONGAN TAMU (REPEATER) -->
                <div class="md:col-span-2">
                    <div class="flex justify-between items-center mb-2">
                        <flux:label class="text-indigo-600 font-semibold">Daftar Pengunjung</flux:label>
                        <flux:button wire:click="tambahTamu" size="sm" variant="outline" icon="plus">Tambah Orang</flux:button>
                    </div>

                    <div class="space-y-3">
                        @foreach($rombonganTamu as $index => $tamu)
                        <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 rounded-lg relative" wire:key="edit-tamu-{{ $index }}">
                            
                            <!-- Tombol Hapus Baris -->
                            @if(count($rombonganTamu) > 1)
                            <button type="button" wire:click="hapusTamu({{ $index }})" class="absolute top-2 right-2 text-red-500 hover:text-red-700 p-1 bg-white dark:bg-zinc-900 rounded shadow-sm">
                                <flux:icon.trash class="w-4 h-4" />
                            </button>
                            @endif
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pr-8">
                                <flux:field>
                                    <flux:label>Nama Tamu {{ $index + 1 }}</flux:label>
                                    <flux:input wire:model.live.debounce.400ms="rombonganTamu.{{ $index }}.nama" list="listTamu" placeholder="Ketik nama tamu..." required />
                                    @if(!empty($rombonganTamu[$index]['tamu_id'])) 
                                        <div class="text-[10px] text-green-500 font-semibold mt-1">✓ Tamu Dikenali (Tersimpan)</div> 
                                    @endif
                                </flux:field>
                                
                                <flux:input wire:model="rombonganTamu.{{ $index }}.wa" label="No Kontak WA" placeholder="Cth: 081234..." />
                                <flux:input wire:model="rombonganTamu.{{ $index }}.pekerjaan" label="Pekerjaan / Jabatan" placeholder="Cth: Instruktur" />
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <!-- END AREA ROMBONGAN -->
                
                <div class="md:col-span-2">
                    <flux:input wire:model="asal_instansi" label="Instansi / Alamat Pengunjung (Asal Tamu)" />
                </div>

                <!-- ... (SISA KODE PETA DAN LAMPIRAN DI BAWAH INI SAMA PERSIS DENGAN KODE AWAL BLI) ... -->
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

            <!-- (BLOK PETA X-DATA SAMA PERSIS SEPERTI SEBELUMNYA) -->
            <div class="md:col-span-2 pt-4" wire:ignore>
                <!-- ... Kode alpine peta Bli tetap biarkan utuh di sini ... -->
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