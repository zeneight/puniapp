<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\WithFileUploads; // <-- UNTUK EDIT BUKTI
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new class extends Component { // Sesuaikan nama class jika berbeda
    use WithPagination, WithFileUploads;
    
    #[Layout('layouts.app')]
    
    public string $search = '';
    public string $sortBy = 'tanggal_bayar'; 
    public string $sortDir = 'desc'; 
    
    public string $filterBulan = '';
    public string $filterTahun = '';
    public string $filterPetugas = ''; // <-- TAMBAHAN: Filter Petugas

    // Variabel Modal Edit Khusus Admin
    public $edit_transaksi_id, $edit_nominal, $edit_keterangan, $edit_bukti_lama, $edit_bukti_baru;
    public $edit_nama_wp, $edit_periode, $edit_tanggal_bayar, $edit_jenis_pembayaran;

    public function mount()
    {
        $this->filterBulan = (string) date('n');
        $this->filterTahun = (string) date('Y');
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterBulan() { $this->resetPage(); }
    public function updatingFilterTahun() { $this->resetPage(); }
    public function updatingFilterPetugas() { $this->resetPage(); }

    public function setSortBy($kolom)
    {
        if ($this->sortBy === $kolom) {
            $this->sortDir = ($this->sortDir === 'asc') ? 'desc' : 'asc';
            return;
        }
        $this->sortBy = $kolom;
        $this->sortDir = 'asc';
    }

    public function resetFilter()
    {
        $this->reset(['search', 'sortBy', 'sortDir', 'filterPetugas']);
        $this->filterBulan = (string) date('n');
        $this->filterTahun = (string) date('Y');
        $this->sortBy = 'tanggal_bayar';
        $this->sortDir = 'desc';
        $this->resetPage();
    }

    // --- FUNGSI EXPORT DATA (CSV) ---
    public function exportData()
    {
        $query = $this->buildQuery(); // Ambil query dengan filter aktif
        $transaksis = $query->get();

        $fileName = 'Laporan_Punia_' . date('Ymd_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($transaksis) {
            $file = fopen('php://output', 'w');
            // Header Kolom Excel
            fputcsv($file, ['Tanggal Masuk', 'Wajib Punia', 'Kategori Punia', 'Periode Tagihan', 'Nominal (Rp)', 'Petugas', 'Keterangan']);

            foreach ($transaksis as $trx) {
                fputcsv($file, [
                    $trx->tanggal_bayar,
                    $trx->wajibPunia->nama ?? 'Terhapus',
                    $trx->jenisPembayaran->nama_kategori ?? 'Umum',
                    'Bulan ' . $trx->periode_bulan . ' / ' . $trx->periode_tahun,
                    $trx->nominal,
                    $trx->user->name ?? '-',
                    $trx->keterangan
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // --- FUNGSI EDIT KHUSUS ADMIN ---
    public function editTransaksi($id)
    {
        $trx = Transaksi::with(['wajibPunia', 'jenisPembayaran'])->findOrFail($id);
        
        $this->edit_transaksi_id = $trx->id;
        $this->edit_nominal = $trx->nominal;
        $this->edit_keterangan = $trx->keterangan;
        $this->edit_bukti_lama = $trx->bukti_transfer;
        
        $this->edit_nama_wp = $trx->wajibPunia->nama ?? 'Data Terhapus';
        $this->edit_periode = 'Bulan ' . $trx->periode_bulan . ' - ' . $trx->periode_tahun;
        $this->edit_tanggal_bayar = $trx->tanggal_bayar;
        $this->edit_jenis_pembayaran = $trx->jenisPembayaran->nama_kategori ?? 'Umum'; 
        
        $this->resetValidation();
        $this->js('$flux.modal("edit-transaksi").show()');
    }

    public function updateTransaksi()
    {
        $this->validate([
            'edit_nominal' => 'required|numeric|min:1',
            'edit_keterangan' => 'nullable|string',
            'edit_bukti_baru' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $trx = Transaksi::findOrFail($this->edit_transaksi_id);
        
        if ($this->edit_bukti_baru) {
            if ($trx->bukti_transfer && Storage::disk('public')->exists($trx->bukti_transfer)) {
                Storage::disk('public')->delete($trx->bukti_transfer);
            }
            $trx->bukti_transfer = $this->edit_bukti_baru->store('bukti_transaksi', 'public');
        }

        $trx->nominal = $this->edit_nominal;
        $trx->keterangan = $this->edit_keterangan;
        $trx->save();

        $this->reset(['edit_transaksi_id', 'edit_nominal', 'edit_keterangan', 'edit_bukti_lama', 'edit_bukti_baru']);
        $this->js('$flux.modal("edit-transaksi").close()');
        \Flux::toast('Data transaksi berhasil diperbarui!', variant: 'success');
    }

    // --- REUSABLE QUERY UNTUK TABLE & EXPORT ---
    private function buildQuery()
    {
        return Transaksi::with(['wajibPunia', 'user', 'jenisPembayaran'])
            ->when(Auth::user()->role === 'inputer', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->when($this->search, function ($q) {
                $q->whereHas('wajibPunia', function ($qWP) {
                    $qWP->where('nama', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterBulan, function ($q) {
                $q->where('periode_bulan', $this->filterBulan);
            })
            ->when($this->filterTahun, function ($q) {
                $q->where('periode_tahun', $this->filterTahun);
            })
            ->when($this->filterPetugas && Auth::user()->role === 'admin', function ($q) {
                $q->where('user_id', $this->filterPetugas);
            })
            ->orderBy($this->sortBy, $this->sortDir);
    }

    public function with()
    {
        $query = $this->buildQuery();

        // FIX BUG: Hitung total nominal secara terpisah sebelum memanggil paginate()
        $totalNominal = (clone $query)->sum('nominal');

        return [
            'transaksis' => $query->paginate(10),
            'totalNominal' => $totalNominal,
            // Kirim daftar petugas ke view khusus untuk Admin
            'daftarPetugas' => Auth::user()->role === 'admin' ? User::where('role', 'inputer')->orderBy('name')->get() : [],
        ];
    }
}
?>

<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl">Data Riwayat Transaksi</flux:heading>
            <flux:subheading>Pantau, kelola, dan unduh seluruh catatan penerimaan punia.</flux:subheading>
        </div>
        
        <div class="flex items-center gap-2">
            <flux:button wire:click="exportData" variant="primary" icon="arrow-down-tray">
                Export Data
            </flux:button>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-4">
        
        <flux:select wire:model.live="filterBulan" class="w-full md:w-36" aria-label="Filter Bulan">
            <flux:select.option value="">Semua Bulan</flux:select.option>
            <flux:select.option value="1">Januari</flux:select.option>
            <flux:select.option value="2">Februari</flux:select.option>
            <flux:select.option value="3">Maret</flux:select.option>
            <flux:select.option value="4">April</flux:select.option>
            <flux:select.option value="5">Mei</flux:select.option>
            <flux:select.option value="6">Juni</flux:select.option>
            <flux:select.option value="7">Juli</flux:select.option>
            <flux:select.option value="8">Agustus</flux:select.option>
            <flux:select.option value="9">September</flux:select.option>
            <flux:select.option value="10">Oktober</flux:select.option>
            <flux:select.option value="11">November</flux:select.option>
            <flux:select.option value="12">Desember</flux:select.option>
        </flux:select>

        <flux:input wire:model.live.debounce.500ms="filterTahun" type="number" placeholder="Tahun..." class="w-full md:w-28" />

        @if(Auth::user()->role === 'admin')
            <flux:select wire:model.live="filterPetugas" class="w-full md:w-44" placeholder="Semua Petugas">
                <flux:select.option value="">Semua Petugas</flux:select.option>
                @foreach($daftarPetugas as $p)
                    <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="Cari donatur..." class="w-full md:w-56 flex-1" />
        
        @if($search !== '' || $filterBulan !== (string) date('n') || $filterTahun !== (string) date('Y') || $filterPetugas !== '')
            <flux:button wire:click="resetFilter" variant="danger" icon="x-mark" class="px-3" tooltip="Reset Filter">
                Reset
            </flux:button>
        @endif
    </div>

    <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg flex items-center justify-between shadow-sm">
        <div class="text-sm text-emerald-800 dark:text-emerald-300 font-medium">Total Nominal dari data yang ditampilkan:</div>
        <div class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 tracking-tight">Rp {{ number_format($totalNominal, 0, ',', '.') }}</div>
    </div>

    <flux:card class="relative">
        
        <div wire:loading wire:target="search, filterBulan, filterTahun, filterPetugas, setSortBy, gotoPage, nextPage, previousPage" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
            <div class="flex items-center gap-3 px-5 py-2.5 bg-white dark:bg-zinc-800 shadow-lg rounded-full border border-zinc-200 dark:border-zinc-700">
                <flux:icon.arrow-path class="w-5 h-5 animate-spin text-zinc-800 dark:text-white" />
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Memproses data...</span>
            </div>
        </div>

        <div wire:loading.class="opacity-40 pointer-events-none transition-opacity duration-200" wire:target="search, filterBulan, filterTahun, filterPetugas, setSortBy, gotoPage, nextPage, previousPage">
            
            <flux:table>
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'tanggal_bayar'" :direction="$sortDir" wire:click="setSortBy('tanggal_bayar')">Tanggal Masuk</flux:table.column>
                    <flux:table.column>Identitas & Kategori</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'periode_bulan'" :direction="$sortDir" wire:click="setSortBy('periode_bulan')">Untuk Periode</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'nominal'" :direction="$sortDir" wire:click="setSortBy('nominal')">Nominal</flux:table.column>
                    
                    @if(Auth::user()->role === 'admin')
                        <flux:table.column>Petugas</flux:table.column>
                        <flux:table.column>Aksi</flux:table.column>
                    @endif
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($transaksis as $trx)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="font-medium">{{ \Carbon\Carbon::parse($trx->tanggal_bayar)->translatedFormat('d M Y') }}</div>
                            <div class="text-[10px] text-zinc-400">{{ $trx->created_at->format('H:i') }} WITA</div>
                        </flux:table.cell>
                        
                        <flux:table.cell>
                            <div class="font-semibold text-zinc-800 dark:text-white">{{ $trx->wajibPunia->nama ?? 'Data Terhapus' }}</div>
                            
                            <div class="text-[11px] text-zinc-600 dark:text-zinc-400 font-medium flex items-center gap-1.5 mt-1">
                                <span class="bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 px-1.5 py-0.5 rounded text-[10px] font-bold">
                                    {{ $trx->jenisPembayaran->nama_kategori ?? 'Umum' }}
                                </span>
                                
                                @if($trx->bukti_transfer)
                                    <flux:icon.paper-clip class="w-3 h-3 text-zinc-400" title="Ada Lampiran Bukti" />
                                @endif
                            </div>
                            
                            <div class="text-xs text-zinc-500 line-clamp-1 mt-1">{{ $trx->keterangan ?? '-' }}</div>
                        </flux:table.cell>

                        <flux:table.cell>Bulan {{ $trx->periode_bulan }} / {{ $trx->periode_tahun }}</flux:table.cell>
                        
                        <flux:table.cell class="font-mono font-semibold text-green-600">Rp {{ number_format($trx->nominal, 0, ',', '.') }}</flux:table.cell>
                        
                        @if(Auth::user()->role === 'admin')
                            <flux:table.cell>
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $trx->user->name ?? '-' }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button 
                                    wire:click="editTransaksi({{ $trx->id }})" 
                                    wire:loading.attr="disabled"
                                    variant="ghost" 
                                    size="sm" 
                                    icon="pencil-square" 
                                    class="text-indigo-600 hover:text-indigo-700 px-1" 
                                    title="Edit Transaksi" 
                                />
                            </flux:table.cell>
                        @endif
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center text-zinc-500 py-6">
                            Tidak ada data transaksi yang sesuai dengan filter.
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            
            <div class="mt-4">
                {{ $transaksis->links() }}
            </div>
        </div>
    </flux:card>

    @if(Auth::user()->role === 'admin')
    <flux:modal name="edit-transaksi" class="md:w-[450px]">
        <form wire:submit.prevent="updateTransaksi" class="space-y-5">
            <div class="border-b pb-3">
                <flux:heading size="lg">Edit Transaksi</flux:heading>
                <div class="text-xs text-zinc-500 mt-1">Admin Mode: Perbarui data jika terjadi kesalahan input.</div>
            </div>
            
            <div class="bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 text-sm space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-zinc-500 dark:text-zinc-400">Wajib Punia</span>
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $edit_nama_wp }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-zinc-500 dark:text-zinc-400">Jenis Punia</span>
                    <span class="bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 px-1.5 py-0.5 rounded text-[10px] font-bold">
                        {{ $edit_jenis_pembayaran }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-zinc-500 dark:text-zinc-400">Periode Tagihan</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $edit_periode }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-zinc-500 dark:text-zinc-400">Tanggal Bayar</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                        {{ $edit_tanggal_bayar ? \Carbon\Carbon::parse($edit_tanggal_bayar)->translatedFormat('d F Y') : '-' }}
                    </span>
                </div>
            </div>
            
            <div x-data="{
                raw: @entangle('edit_nominal'),
                formatted: '',
                
                init() {
                    // Format saat modal pertama kali terbuka jika sudah ada isinya
                    if (this.raw) {
                        this.formatted = new Intl.NumberFormat('id-ID').format(this.raw);
                    }
                    // Pantau perubahan dari Livewire (saat admin klik tombol edit data lain)
                    $watch('raw', value => {
                        this.formatted = value ? new Intl.NumberFormat('id-ID').format(value) : '';
                    });
                },
                
                formatInput(value) {
                    let angkaBersih = value.replace(/[^0-9]/g, '');
                    this.raw = angkaBersih ? parseInt(angkaBersih) : null;
                    this.formatted = angkaBersih ? new Intl.NumberFormat('id-ID').format(angkaBersih) : '';
                }
            }">
                <flux:field>
                    <flux:label>Nominal Pembayaran</flux:label>
                    <flux:input.group>
                        <flux:input.group.prefix>Rp</flux:input.group.prefix>
                        <flux:input x-model="formatted" @input="formatInput($event.target.value)" required placeholder="Contoh: 150.000" />
                    </flux:input.group>
                </flux:field>
            </div>
            
            <flux:textarea wire:model="edit_keterangan" label="Keterangan" rows="2" />
            
            <flux:field class="bg-zinc-50 dark:bg-zinc-800 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <flux:label class="mb-2">Perbarui Bukti Dokumen</flux:label>
                
                @if($edit_bukti_lama)
                    <div class="flex items-center gap-2 text-sm mb-3 bg-white dark:bg-zinc-900 p-2 rounded border border-zinc-200 dark:border-zinc-600">
                        <flux:icon.document-check class="w-4 h-4 text-green-500" />
                        <a href="{{ url('storage/' . $edit_bukti_lama) }}" target="_blank" class="text-indigo-600 hover:text-indigo-700 underline text-xs font-medium">Lihat Dokumen Saat Ini</a>
                    </div>
                @endif
                
                <flux:input type="file" wire:model="edit_bukti_baru" accept="image/*,.pdf" class="text-sm" />
                <div wire:loading wire:target="edit_bukti_baru" class="text-xs text-indigo-600 mt-1">Mengunggah file baru...</div>
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" x-on:click="$flux.modal('edit-transaksi').close()" variant="ghost">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
            </div>
        </form>
    </flux:modal>
    @endif

</div>