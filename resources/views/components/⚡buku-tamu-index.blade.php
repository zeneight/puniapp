<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

// Pastikan model-model baru ini sudah Bli buat ya!
use App\Models\Tamu;
use App\Models\KunjunganTamu;
use App\Models\RiwayatTindakLanjut;
use App\Models\Banjar;

new class extends Component
{
    use WithPagination;

    #[Layout('layouts.app')]

    // Variabel Filter & Pencarian
    public $search = '';
    public $filter_tanggal = '';
    public $filter_status = '';
    public $filter_prioritas = '';

    // Variabel Master Tamu (Auto-fill)
    public $tamu_id = null; // Jika null berarti tamu baru
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

    public $edit_kunjungan_id;

    // Variabel Hapus
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
            'alasan_kunjungan', 'banjar_id', 'petugas', 'tindak_lanjut_baru', 'kunjungan_id'
        ]);
        $this->prioritas = 'Prioritas 3';
        $this->tanggal_kunjungan = date('Y-m-d');
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
            // Biarkan teks ketikan ada, tapi reset ID karena ini tamu baru
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
        ]);

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

        // Hitung Kunjungan Ke Berapa
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
        ]);

        // 3. Simpan Riwayat Awal (Timeline 1)
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

        // --- PERBAIKAN DI SINI ---
        // Jika statusnya masih 'Tamu masuk', otomatis arahkan default ke 'Proses'
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

        // Tambah Riwayat Baru
        RiwayatTindakLanjut::create([
            'kunjungan_id' => $this->kunjungan_id,
            'status_log' => $this->status_baru,
            'catatan' => $this->tindak_lanjut_baru
        ]);

        // Update Status Terakhir di Tabel Kunjungan
        $this->detailKunjungan->update(['status' => $this->status_baru]);

        // Refresh Data Timeline agar langsung muncul tanpa loading ulang modal
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
        // 1. Tutup modal detail yang sedang terbuka
        $this->js('$flux.modal("detail-kunjungan").close()');

        // 2. Tarik data dari database
        $kunjungan = KunjunganTamu::with('tamu')->findOrFail($id);
        
        // 3. Isi state form dengan data lama
        $this->edit_kunjungan_id = $kunjungan->id;
        $this->tamu_id = $kunjungan->tamu_id;
        
        // Data Tamu
        $this->nama_pengunjung = $kunjungan->tamu->nama_pengunjung;
        $this->kontak_wa = $kunjungan->tamu->kontak_wa;
        $this->pekerjaan_status = $kunjungan->tamu->pekerjaan_status;
        $this->asal_instansi = $kunjungan->tamu->asal_instansi;

        // Data Kunjungan
        $this->tanggal_kunjungan = $kunjungan->tanggal_kunjungan;
        $this->banjar_id = $kunjungan->banjar_id;
        $this->petugas = $kunjungan->petugas;
        $this->alasan_kunjungan = $kunjungan->alasan_kunjungan;
        $this->prioritas = $kunjungan->prioritas;

        $this->resetValidation();
        
        // 4. Buka modal edit (pakai setTimeout agar modal sebelumnya benar-benar tertutup dulu)
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
        ]);

        $kunjungan = KunjunganTamu::findOrFail($this->edit_kunjungan_id);

        // 1. Update Data Master Tamu
        if ($kunjungan->tamu_id) {
            Tamu::where('id', $kunjungan->tamu_id)->update([
                'nama_pengunjung' => $this->nama_pengunjung,
                'kontak_wa' => $this->kontak_wa,
                'pekerjaan_status' => $this->pekerjaan_status,
                'asal_instansi' => $this->asal_instansi,
            ]);
        }

        // 2. Update Data Kunjungan
        $kunjungan->update([
            'tanggal_kunjungan' => $this->tanggal_kunjungan,
            'banjar_id' => $this->banjar_id,
            'petugas' => $this->petugas,
            'alasan_kunjungan' => $this->alasan_kunjungan,
            'prioritas' => $this->prioritas,
        ]);

        $this->js('$flux.modal("edit-kunjungan").close()');
        $this->batal(); // Bersihkan state
        \Flux::toast('Data utama kunjungan berhasil diperbarui!', variant: 'success');
    }

    public function with()
    {
        $totalKeseluruhan = KunjunganTamu::count();

        // Query relasi Kunjungan -> Tamu -> Banjar
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
            'daftarNamaTamu' => Tamu::pluck('nama_pengunjung'), // Untuk Auto-fill datalist
            'daftarBanjar' => Banjar::orderBy('nama_banjar')->get(),
            'totalKeseluruhan' => $totalKeseluruhan,
            'totalDifilter' => $totalDifilter,
        ];
    }
}
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

    <!-- Filter (Tetap sama seperti kode Bli sebelumnya) -->
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
                                <div class="font-medium text-zinc-900 dark:text-white line-clamp-1">{{ $kunjungan->alasan_kunjungan }}</div>
                                <div class="text-xs text-zinc-500 mt-1">Petugas: {{ $kunjungan->petugas }} | {{ $kunjungan->banjar->nama_banjar ?? '-' }}</div>
                                <div class="text-[10px] text-zinc-400 mt-0.5">{{ \Carbon\Carbon::parse($kunjungan->tanggal_kunjungan)->translatedFormat('l, d F Y') }}</div>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <flux:badge size="sm" color="{{ $kunjungan->prioritas == 'Prioritas 1' ? 'danger' : ($kunjungan->prioritas == 'Prioritas 2' ? 'warning' : 'zinc') }}">{{ $kunjungan->prioritas }}</flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge size="sm" color="{{ $kunjungan->status == 'Selesai' ? 'success' : ($kunjungan->status == 'Proses' ? 'blue' : 'zinc') }}">{{ $kunjungan->status }}</flux:badge>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <!-- HANYA 1 TOMBOL: Detail & Tindak Lanjut -->
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

            <!-- Datalist Sugesti Nama -->
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

                <div class="md:col-span-2 pt-3 border-t border-zinc-100 dark:border-zinc-800"></div>

                <!-- Input Kunjungan Baru -->
                <flux:field>
                    <flux:label>Lokasi Terkait (Wilayah Banjar)</flux:label>
                    <flux:select wire:model="banjar_id" required>
                        <option value="">Pilih Banjar...</option>
                        @foreach($daftarBanjar as $b) <option value="{{ $b->id }}">{{ $b->nama_banjar }}</option> @endforeach
                    </flux:select>
                </flux:field>

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

            <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Kunjungan</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- MODAL 2: DETAIL & TIMELINE (VIEW + EDIT JADI SATU) -->
    <flux:modal name="detail-kunjungan" class="md:w-[650px]">
        @if($detailKunjungan)
        <div class="flex flex-col h-full max-h-[85vh]">
            
            <!-- HEADER -->
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-4 shrink-0">
                <div class="flex justify-between items-start">
                    <div>
                        <flux:heading size="lg">
                            {{ $detailKunjungan->tamu->nama_pengunjung }}

                            <!-- Tombol Edit Data Utama Kunjungan -->
                            <flux:button wire:click="bukaEditKunjungan({{ $detailKunjungan->id }})" size="sm" variant="subtle" icon="pencil-square" class="ml-2 text-indigo-500" />

                        </flux:heading>
                        <flux:subheading>{{ $detailKunjungan->tamu->kontak_wa ?? '-' }} • {{ $detailKunjungan->tamu->pekerjaan_status ?? '-' }}</flux:subheading>
                    </div>
                    <flux:badge color="{{ $detailKunjungan->status == 'Selesai' ? 'success' : 'warning' }}">{{ $detailKunjungan->status }}</flux:badge>
                </div>
                
                <div class="mt-3 bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-lg text-sm text-zinc-700 dark:text-zinc-300">
                    <div class="font-semibold text-zinc-900 dark:text-white mb-1">Maksud Kunjungan:</div>
                    <p>{{ $detailKunjungan->alasan_kunjungan }}</p>
                    <div class="mt-2 pt-2 border-t border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500">
                        Lokasi: {{ $detailKunjungan->banjar->nama_banjar ?? '-' }} | Petugas: {{ $detailKunjungan->petugas }} | Prioritas: {{ $detailKunjungan->prioritas }}
                    </div>
                </div>
            </div>

            <!-- BODY TIMELINE -->
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

            <!-- FOOTER FORM TINDAK LANJUT -->
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

                <div class="md:col-span-2 pt-3 border-t border-zinc-100 dark:border-zinc-800"></div>

                <flux:field>
                    <flux:label>Lokasi Terkait (Wilayah Banjar)</flux:label>
                    <flux:select wire:model="banjar_id" required>
                        <option value="">Pilih Banjar...</option>
                        @foreach($daftarBanjar as $b) <option value="{{ $b->id }}">{{ $b->nama_banjar }}</option> @endforeach
                    </flux:select>
                </flux:field>

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
</div>