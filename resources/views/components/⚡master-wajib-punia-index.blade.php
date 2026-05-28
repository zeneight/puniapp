<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

use App\Models\WajibPunia;
use App\Models\Banjar;
use App\Models\Kategori;
use App\Models\User;

new class extends Component {
    use WithPagination;

    #[Layout('layouts.app')]

    // Variabel Form & State
    public ?int $wajib_punia_id = null;
    public string $nama = '';
    public string $alamat = '';
    public string $banjar_id = '';
    public string $kategori_id = '';
    public string $jenis_usaha = '';
    public int $jumlah_unit = 1;
    public int $pagu_dudukan = 0;
    public string $kontak_pengelola = '';
    public string $user_id = '';

    // untuk Search & Sort
    public string $search = '';
    public string $sortBy = 'nama'; // Default urut berdasarkan nama
    public string $sortDir = 'asc'; // Default A-Z
    
    public string $filterKategori = '';
    public string $filterPetugas = '';

    // Fungsi reset halaman
    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterKategori() { $this->resetPage(); }
    public function updatingFilterPetugas() { $this->resetPage(); }

    // reset
    public function resetFilter()
    {
        $this->reset(['search', 'filterKategori', 'filterPetugas', 'sortBy', 'sortDir']);
        $this->sortBy = 'nama'; // Kembalikan default sort
        $this->sortDir = 'asc';
        $this->resetPage();
    }

    // Fungsi untuk membalikkan arah urutan saat header tabel diklik
    public function setSortBy($kolom)
    {
        if ($this->sortBy === $kolom) {
            $this->sortDir = ($this->sortDir === 'asc') ? 'desc' : 'asc';
            return;
        }
        $this->sortBy = $kolom;
        $this->sortDir = 'asc';
    }

    protected function rules()
    {
        return [
            'nama' => 'required|min:3',
            'alamat' => 'required',
            'banjar_id' => 'required|exists:banjars,id',
            'kategori_id' => 'required|exists:kategoris,id',
            'user_id' => 'nullable|exists:users,id',
            'pagu_dudukan' => 'required|numeric|min:0',
            'jumlah_unit' => 'required|numeric|min:1',
        ];
    }

    // --- FUNGSI CREATE ---
    public function simpan()
    {
        $this->validate();

        WajibPunia::create([
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'banjar_id' => $this->banjar_id,
            'kategori_id' => $this->kategori_id,
            'user_id' => $this->user_id ?: Auth::id(), // Set petugas yang input data, default ke user saat ini
            'jenis_usaha' => $this->jenis_usaha,
            'jumlah_unit' => $this->jumlah_unit,
            'pagu_dudukan' => $this->pagu_dudukan,
            'kontak_pengelola' => $this->kontak_pengelola,
        ]);

        $this->resetForm();
        $this->js('$flux.modal("tambah-wp").close()');
    }

    // --- FUNGSI UPDATE (EDIT) ---
    public function edit($id)
    {
        $wp = WajibPunia::findOrFail($id);
        
        $this->wajib_punia_id = $wp->id;
        $this->nama = $wp->nama;
        $this->alamat = $wp->alamat;
        // Konversi ke string agar sinkron dengan flux:select
        $this->banjar_id = (string) $wp->banjar_id;
        $this->kategori_id = (string) $wp->kategori_id;
        $this->user_id = (string) $wp->user_id;
        $this->jenis_usaha = $wp->jenis_usaha ?? '';
        $this->jumlah_unit = $wp->jumlah_unit;
        $this->pagu_dudukan = $wp->pagu_dudukan;
        $this->kontak_pengelola = $wp->kontak_pengelola ?? '';

        $this->resetValidation();
        $this->js('$flux.modal("edit-wp").show()');
    }

    public function update()
    {
        $this->validate();

        $wp = WajibPunia::findOrFail($this->wajib_punia_id);
        $wp->update([
            'nama' => $this->nama,
            'alamat' => $this->alamat,
            'banjar_id' => $this->banjar_id,
            'kategori_id' => $this->kategori_id,
            'user_id' => $this->user_id ?: null,
            'jenis_usaha' => $this->jenis_usaha,
            'jumlah_unit' => $this->jumlah_unit,
            'pagu_dudukan' => $this->pagu_dudukan,
            'kontak_pengelola' => $this->kontak_pengelola,
        ]);

        $this->resetForm();
        $this->js('$flux.modal("edit-wp").close()');
    }

    // --- FUNGSI DELETE ---
    public function konfirmasiHapus($id)
    {
        $this->wajib_punia_id = $id;
        $this->js('$flux.modal("hapus-wp").show()');
    }

    public function destroy()
    {
        WajibPunia::findOrFail($this->wajib_punia_id)->delete();
        $this->resetForm();
        $this->js('$flux.modal("hapus-wp").close()');
    }

    // --- UTILITY ---
    public function resetForm()
    {
        $this->reset(['wajib_punia_id', 'nama', 'alamat', 'banjar_id', 'kategori_id', 'jenis_usaha', 'jumlah_unit', 'pagu_dudukan', 'kontak_pengelola']);
        $this->resetValidation();
    }

    public function with()
    {
        $query = WajibPunia::with(['banjar', 'kategori', 'user'])
            // Filter Search (dibungkus sub-query agar aman)
            ->when($this->search, function ($q) {
                $q->where(function ($subQ) {
                    $subQ->where('nama', 'like', '%' . $this->search . '%')
                         ->orWhereHas('banjar', function ($qBanjar) {
                             $qBanjar->where('nama_banjar', 'like', '%' . $this->search . '%');
                         });
                });
            })
            // Filter Kategori
            ->when($this->filterKategori, function ($q) {
                $q->where('kategori_id', $this->filterKategori);
            })
            // Filter Petugas
            ->when($this->filterPetugas, function ($q) {
                $q->where('user_id', $this->filterPetugas);
            })
            ->orderBy($this->sortBy, $this->sortDir);

        return [
            'wajibPunias' => $query->paginate(10),
            'banjars' => Banjar::all(),
            'kategoris' => Kategori::all(),
            'petugas' => User::where('role', 'inputer')->get(),
        ];
    }
};
?>

<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl">Data Master Wajib Punia</flux:heading>
            <flux:subheading>Daftar seluruh tempat usaha, proyek, atau domisili.</flux:subheading>
        </div>
        
        <div class="flex flex-col md:flex-row w-full md:w-auto items-center gap-2">
            
            <flux:select wire:model.live="filterKategori" class="w-full md:w-40" aria-label="Filter Kategori">
                <flux:select.option value="">Semua Kategori</flux:select.option>
                @foreach($kategoris as $kat)
                    <flux:select.option value="{{ $kat->id }}">{{ $kat->nama_kategori }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="filterPetugas" class="w-full md:w-40" aria-label="Filter Petugas">
                <flux:select.option value="">Semua Petugas</flux:select.option>
                @foreach($petugas as $p)
                    <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="Cari nama / banjar..." class="w-full md:w-64" />
            
            @if($search !== '' || $filterKategori !== '' || $filterPetugas !== '')
                <flux:button wire:click="resetFilter" variant="danger" icon="x-mark" class="px-3" tooltip="Reset Pencarian">
                    Reset
                </flux:button>
            @endif

            <flux:button variant="primary" icon="plus" x-on:click="$flux.modal('tambah-wp').show()">
                Registrasi
            </flux:button>
        </div>
    </div>

    <flux:card class="relative">
        <div wire:loading class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
            <div class="flex items-center gap-3 px-5 py-2.5 bg-white dark:bg-zinc-800 shadow-lg rounded-full border border-zinc-200 dark:border-zinc-700">
                <svg class="w-5 h-5 animate-spin text-zinc-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Memuat data...</span>
            </div>
        </div>

        <div wire:loading.class="opacity-50 pointer-events-none transition-opacity duration-200">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'nama'" :direction="$sortDir" wire:click="setSortBy('nama')">Nama / Usaha</flux:table.column>
                    <flux:table.column>Banjar</flux:table.column>
                    <flux:table.column>Kategori</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'pagu_dudukan'" :direction="$sortDir" wire:click="setSortBy('pagu_dudukan')">Pagu Bulanan</flux:table.column>
                    <flux:table.column>Petugas</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($wajibPunias as $wp)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="font-semibold text-zinc-800 dark:text-white">{{ $wp->nama }}</div>
                            <div class="text-xs text-zinc-500">{{ $wp->alamat }}</div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $wp->banjar->nama_banjar ?? '-' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" inset="top bottom">{{ $wp->kategori->nama_kategori ?? '-' }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono">Rp {{ number_format($wp->pagu_dudukan, 0, ',', '.') }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $wp->user->name ?? 'Belum Diatur' }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button wire:click="edit({{ $wp->id }})" size="sm" variant="ghost" icon="pencil-square" />
                            <flux:button wire:click="konfirmasiHapus({{ $wp->id }})" size="sm" variant="ghost" color="danger" icon="trash" />
                        </flux:table.cell>
                    </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
        
        <div class="mt-4">
            {{ $wajibPunias->links() }}
        </div>
    </flux:card>

    <flux:modal name="tambah-wp" class="md:min-w-[600px]" wire:close="resetForm">
        <form wire:submit="simpan" class="space-y-4">
            <div>
                <flux:heading size="lg">Registrasi Wajib Punia Baru</flux:heading>
                <flux:subheading>Lengkapi data sesuai lembar survei lapangan.</flux:subheading>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="nama" label="Nama Usaha / Personal" placeholder="Contoh: Mini Mart CG68" />
                <flux:input wire:model="kontak_pengelola" label="Kontak / No. HP" placeholder="Contoh: 081234567xx" />
            </div>

            <flux:textarea wire:model="alamat" label="Alamat Lengkap" placeholder="Nama Jalan, patokan, dll." />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="banjar_id" label="Wilayah Banjar" placeholder="Pilih Banjar...">
                    @foreach($banjars as $banjar)
                        <flux:select.option value="{{ $banjar->id }}">{{ $banjar->nama_banjar }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="user_id" label="Petugas Penagih (Opsional)" placeholder="Pilih Petugas...">
                    @foreach($petugas as $p)
                        <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="kategori_id" label="Kategori Pembayaran" placeholder="Pilih Kategori...">
                    @foreach($kategoris as $kat)
                        <flux:select.option value="{{ $kat->id }}">{{ $kat->nama_kategori }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <flux:input wire:model="jenis_usaha" label="Jenis Usaha (Opsional)" placeholder="cth: Toko, Villa" />
                <flux:input wire:model="jumlah_unit" type="number" label="Jumlah Unit" />
                <flux:input wire:model="pagu_dudukan" type="number" label="Pagu Nominal (Rp)" placeholder="cth: 200000" />
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Daftarkan</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-wp" class="md:min-w-[600px]" wire:close="resetForm">
        <form wire:submit="update" class="space-y-4">
            <div>
                <flux:heading size="lg">Edit Data Wajib Punia</flux:heading>
                <flux:subheading>Ubah informasi profil atau pagu berkala wajib punia.</flux:subheading>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="nama" label="Nama Usaha / Personal" />
                <flux:input wire:model="kontak_pengelola" label="Kontak / No. HP" />
            </div>

            <flux:textarea wire:model="alamat" label="Alamat Lengkap" />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model="banjar_id" label="Wilayah Banjar">
                    @foreach($banjars as $banjar)
                        <flux:select.option value="{{ $banjar->id }}">{{ $banjar->nama_banjar }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="user_id" label="Petugas Penagih (Opsional)">
                    @foreach($petugas as $p)
                        <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="kategori_id" label="Kategori Pembayaran">
                    @foreach($kategoris as $kat)
                        <flux:select.option value="{{ $kat->id }}">{{ $kat->nama_kategori }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <flux:input wire:model="jenis_usaha" label="Jenis Usaha (Opsional)" />
                <flux:input wire:model="jumlah_unit" type="number" label="Jumlah Unit" />
                <flux:input wire:model="pagu_dudukan" type="number" label="Pagu Nominal (Rp)" />
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="hapus-wp" class="min-w-[400px]" wire:close="resetForm">
        <div class="mb-4">
            <flux:heading size="lg">Hapus Data Wajib Punia?</flux:heading>
            <flux:subheading>Menghapus data ini akan memutus riwayat pembayaran terkait. Yakin?</flux:subheading>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
            <flux:button wire:click="destroy" variant="danger">Ya, Hapus</flux:button>
        </div>
    </flux:modal>
</div>