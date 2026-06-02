<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\WajibPunia;
use App\Models\Banjar;
use App\Models\Pemilik;
use App\Models\JenisUsaha;

new class extends Component {
    use WithPagination;

    #[Layout('layouts.app')]

    // Variabel Form
    public ?int $wajib_punia_id = null;
    public string $nama = '';
    public string $no_registrasi = '';
    public string $tgl_registrasi = '';
    public ?int $pagu_dudukan = null;
    public bool $is_active = true;
    public string $alamat = '';
    
    // Variabel Foreign Keys (Relasi)
    public $pemilik_id = '';
    public $jenis_usaha_id = '';
    public $banjar_id = '';

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    protected function rules()
    {
        return [
            'nama' => 'required|string|min:3|max:150',
            'no_registrasi' => 'nullable|string|unique:wajib_punias,no_registrasi,' . $this->wajib_punia_id,
            'tgl_registrasi' => 'nullable|date',
            'pagu_dudukan' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'pemilik_id' => 'required|exists:pemiliks,id',
            'jenis_usaha_id' => 'required|exists:jenis_usahas,id',
            'banjar_id' => 'required|exists:banjars,id',
            'alamat' => 'nullable|string|max:255',
        ];
    }

    // --- FUNGSI CREATE ---
    public function simpan()
    {
        $this->validate();
        WajibPunia::create([
            'nama' => $this->nama,
            'no_registrasi' => $this->no_registrasi,
            'tgl_registrasi' => $this->tgl_registrasi,
            'pagu_dudukan' => $this->pagu_dudukan,
            'is_active' => $this->is_active,
            'pemilik_id' => $this->pemilik_id,
            'jenis_usaha_id' => $this->jenis_usaha_id,
            'banjar_id' => $this->banjar_id,
            'alamat' => $this->alamat,
        ]);
        
        $this->batal();
        $this->js('$flux.modal("tambah-wp").close()');
    }

    // --- FUNGSI UPDATE ---
    public function edit($id)
    {
        $wp = WajibPunia::findOrFail($id);
        
        $this->wajib_punia_id = $wp->id;
        $this->nama = $wp->nama;
        $this->no_registrasi = $wp->no_registrasi ?? '';
        $this->tgl_registrasi = $wp->tgl_registrasi ?? '';
        $this->pagu_dudukan = $wp->pagu_dudukan;
        $this->is_active = (bool) $wp->is_active;
        $this->pemilik_id = (string) $wp->pemilik_id;
        $this->jenis_usaha_id = (string) $wp->jenis_usaha_id;
        $this->banjar_id = (string) $wp->banjar_id;
        $this->alamat = $wp->alamat ?? '';
        
        $this->resetValidation();
        $this->js('$flux.modal("edit-wp").show()');
    }

    public function update()
    {
        $this->validate();
        
        $wp = WajibPunia::findOrFail($this->wajib_punia_id);
        $wp->update([
            'nama' => $this->nama,
            'no_registrasi' => $this->no_registrasi,
            'tgl_registrasi' => $this->tgl_registrasi,
            'pagu_dudukan' => $this->pagu_dudukan,
            'is_active' => $this->is_active,
            'pemilik_id' => $this->pemilik_id,
            'jenis_usaha_id' => $this->jenis_usaha_id,
            'banjar_id' => $this->banjar_id,
            'alamat' => $this->alamat,
        ]);
        
        $this->batal();
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
        $this->batal();
        $this->js('$flux.modal("hapus-wp").close()');
    }

    // --- FUNGSI UTILITY ---
    public function batal()
    {
        $this->reset([
            'wajib_punia_id', 'nama', 'no_registrasi', 'tgl_registrasi', 
            'pagu_dudukan', 'pemilik_id', 'jenis_usaha_id', 'banjar_id', 'alamat'
        ]);

        $this->pemilik_id = '';
        $this->jenis_usaha_id = '';
        $this->banjar_id = '';

        $this->is_active = true;
        $this->resetValidation();
    }

    public function with()
    {
        return [
            // Eager loading relasi agar tidak query N+1 di tabel HTML
            'wajibPunias' => WajibPunia::with(['banjar', 'pemilik', 'jenisUsaha'])
                                       ->where('nama', 'like', '%' . $this->search . '%')
                                       ->orWhere('no_registrasi', 'like', '%' . $this->search . '%')
                                       ->orderBy('nama', 'asc')
                                       ->paginate(10),
            
            // Mengambil data master untuk dropdown form
            'daftarBanjar' => Banjar::orderBy('nama_banjar', 'asc')->get(),
            'daftarPemilik' => Pemilik::orderBy('nama_pemilik', 'asc')->get(),
            'daftarJenisUsaha' => JenisUsaha::orderBy('nama_jenis_usaha', 'asc')->get(),
        ];
    }
};
?>

<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl">Master Wajib Punia</flux:heading>
            <flux:subheading>Kelola data tempat usaha dan profil Wajib Punia.</flux:subheading>
        </div>
        
        <div class="flex w-full md:w-auto items-center gap-2">
            <flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="Cari nama atau no reg..." class="w-full md:w-64" />
            <flux:button variant="primary" icon="plus" x-on:click="$flux.modal('tambah-wp').show()">
                Tambah Data
            </flux:button>
        </div>
    </div>

    <flux:card class="relative">
        <div wire:loading wire:target="search, gotoPage, nextPage, previousPage" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
            <span class="text-xs font-medium">Memuat...</span>
        </div>

        <div wire:loading.class="opacity-40" wire:target="search, gotoPage, nextPage, previousPage">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nama Usaha / Lokasi</flux:table.column>
                    <flux:table.column>Info Pemilik</flux:table.column>
                    <flux:table.column>Pagu Punia</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($wajibPunias as $wp)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="font-semibold">{{ $wp->nama }}</div>
                            <div class="text-xs text-zinc-500">
                                {{ $wp->jenisUsaha->nama_jenis_usaha ?? '-' }} • Br. {{ $wp->banjar->nama_banjar ?? '-' }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="font-medium text-sm">{{ $wp->pemilik->nama_pemilik ?? '-' }}</div>
                            <div class="text-[10px] text-zinc-400">Reg: {{ $wp->no_registrasi ?? '-' }}</div>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-sm text-emerald-600 font-semibold">
                            Rp {{ number_format($wp->pagu_dudukan, 0, ',', '.') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($wp->is_active)
                                <flux:badge color="success" size="sm" inset="top bottom">Aktif</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm" inset="top bottom">Nonaktif</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button wire:click="edit({{ $wp->id }})" size="sm" variant="ghost" icon="pencil-square" />
                            <flux:button wire:click="konfirmasiHapus({{ $wp->id }})" size="sm" variant="ghost" color="danger" icon="trash" />
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-500 py-4">Data tidak ditemukan.</flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            
            <div class="mt-4">
                {{ $wajibPunias->links() }}
            </div>
        </div>
    </flux:card>

    <flux:modal name="tambah-wp" class="md:w-[700px]" wire:close="batal">
        <form wire:submit="simpan" class="space-y-5">
            <div>
                <flux:heading size="lg">Tambah Wajib Punia</flux:heading>
                <flux:subheading>Isi formulir relasi data berikut.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:select wire:model="pemilik_id" label="Pemilik / Donatur Utama" placeholder="Pilih Pemilik...">
                    @foreach($daftarPemilik as $pemilik)
                        <flux:select.option value="{{ $pemilik->id }}">{{ $pemilik->nama_pemilik }}</flux:select.option>
                    @endforeach
                </flux:select>
                
                <flux:input wire:model="nama" label="Nama Tempat Usaha" placeholder="Contoh: Villa Kahayana" />
                <div class="md:col-span-2">
                    <flux:textarea wire:model="alamat" label="Alamat Lengkap Usaha" rows="2" placeholder="Contoh: Jl. Hayam Wuruk No. 123, Br. Kedaton" />
                </div>
                
                <flux:select wire:model="jenis_usaha_id" label="Jenis Usaha" placeholder="Pilih Kategori...">
                    @foreach($daftarJenisUsaha as $ju)
                        <flux:select.option value="{{ $ju->id }}">{{ $ju->nama_jenis_usaha }}</flux:select.option>
                    @endforeach
                </flux:select>
                
                <flux:select wire:model="banjar_id" label="Wilayah Banjar" placeholder="Pilih Banjar...">
                    @foreach($daftarBanjar as $b)
                        <flux:select.option value="{{ $b->id }}">{{ $b->nama_banjar }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="no_registrasi" label="Nomor Registrasi" placeholder="Opsional" />
                <flux:input wire:model="tgl_registrasi" type="date" label="Tanggal Registrasi" />
                
                <flux:input wire:model="pagu_dudukan" type="number" label="Pagu Dudukan (Rp)" placeholder="Nominal tagihan bulanan" />
                
                <div class="flex items-center h-full pt-6">
                    <flux:switch wire:model="is_active" label="Status Aktif" />
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Data</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-wp" class="md:w-[700px]" wire:close="batal">
        <form wire:submit="update" class="space-y-5">
            <div>
                <flux:heading size="lg">Edit Wajib Punia</flux:heading>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:select wire:model="pemilik_id" label="Pemilik / Donatur Utama" placeholder="Pilih Pemilik...">
                    @foreach($daftarPemilik as $pemilik)
                        <flux:select.option value="{{ $pemilik->id }}">{{ $pemilik->nama_pemilik }}</flux:select.option>
                    @endforeach
                </flux:select>
                
                <flux:input wire:model="nama" label="Nama Tempat Usaha" />
                <div class="md:col-span-2">
                    <flux:textarea wire:model="alamat" label="Alamat Lengkap Usaha" rows="2" placeholder="Contoh: Jl. Hayam Wuruk No. 123, Br. Kedaton" />
                </div>

                <flux:select wire:model="jenis_usaha_id" label="Jenis Usaha" placeholder="Pilih Kategori...">
                    @foreach($daftarJenisUsaha as $ju)
                        <flux:select.option value="{{ $ju->id }}">{{ $ju->nama_jenis_usaha }}</flux:select.option>
                    @endforeach
                </flux:select>
                
                <flux:select wire:model="banjar_id" label="Wilayah Banjar" placeholder="Pilih Banjar...">
                    @foreach($daftarBanjar as $b)
                        <flux:select.option value="{{ $b->id }}">{{ $b->nama_banjar }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="no_registrasi" label="Nomor Registrasi" />
                <flux:input wire:model="tgl_registrasi" type="date" label="Tanggal Registrasi" />
                
                <flux:input wire:model="pagu_dudukan" type="number" label="Pagu Dudukan (Rp)" />
                
                <div class="flex items-center h-full pt-6">
                    <flux:switch wire:model="is_active" label="Status Aktif" />
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Update Data</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="hapus-wp" class="min-w-[400px]" wire:close="batal">
        <div class="mb-4">
            <flux:heading size="lg">Hapus Data?</flux:heading>
            <flux:subheading>Menghapus data ini juga akan menghapus riwayat dokumen terkait. Tindakan ini tidak dapat dibatalkan.</flux:subheading>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
            <flux:button wire:click="destroy" variant="danger">Ya, Hapus</flux:button>
        </div>
    </flux:modal>
</div>