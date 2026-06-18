<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Banjar;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    #[Layout('layouts.app')] 
    
    // Variabel untuk menampung inputan dan ID data yang dipilih
    public ?int $banjar_id = null;
    public string $nama_banjar = '';

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // Kita gunakan method rules() alih-alih attribute #[Validate] 
    // agar pengecekan "unique" bisa mengecualikan ID yang sedang diedit
    protected function rules()
    {
        return [
            'nama_banjar' => 'required|min:3|unique:banjars,nama_banjar,' . $this->banjar_id,
        ];
    }

    // --- FUNGSI CREATE ---
    public function simpan()
    {
        $this->validate();
        Banjar::create(['nama_banjar' => $this->nama_banjar]);
        $this->batal(); // Reset form
        $this->js('$flux.modal("tambah-banjar").close()'); 
        \Flux::toast('Data berhasil ditambahkan.', variant: 'success');
    }

    // --- FUNGSI UPDATE ---
    public function edit($id)
    {
        $banjar = Banjar::findOrFail($id);
        $this->banjar_id = $banjar->id;
        $this->nama_banjar = $banjar->nama_banjar;
        
        $this->resetValidation(); // Hapus pesan error sebelumnya (jika ada)
        $this->js('$flux.modal("edit-banjar").show()');
    }

    public function update()
    {
        $this->validate();
        
        $banjar = Banjar::findOrFail($this->banjar_id);
        $banjar->update(['nama_banjar' => $this->nama_banjar]);
        
        $this->batal();
        $this->js('$flux.modal("edit-banjar").close()');
        \Flux::toast('Data berhasil diperbarui.', variant: 'success');
    }

    // --- FUNGSI DELETE ---
    public function konfirmasiHapus($id)
    {
        // Hitung jumlah data Wajib Punia yang terhubung ke Banjar ini
        // (Pastikan relasi 'wajibPunias' sudah ada di model Banjar.php)
        $banjar = Banjar::withCount('wajibPunias')->findOrFail($id);
        
        if ($banjar->wajib_punias_count > 0) {
            \Flux::toast('Aksi ditolak! Banjar ini masih digunakan oleh ' . $banjar->wajib_punias_count . ' data Wajib Punia.', variant: 'danger');
            return; // Hentikan proses, modal hapus tidak akan terbuka
        }

        $this->banjar_id = $id;
        $this->js('$flux.modal("hapus-banjar").show()');
    }

    public function destroy()
    {
        try {
            Banjar::findOrFail($this->banjar_id)->delete();
            
            $this->batal(); // Reset form
            $this->js('$flux.modal("hapus-banjar").close()');
            \Flux::toast('Banjar berhasil dihapus.', variant: 'success');
            
        } catch (\Illuminate\Database\QueryException $e) {
            // Jika error code-nya 23000 (Integrity Constraint / Foreign Key Fail)
            if ($e->getCode() == 23000) {
                $this->js('$flux.modal("hapus-banjar").close()');
                \Flux::toast('Gagal menghapus! Data Banjar masih terikat dengan data lain di sistem.', variant: 'danger');
            } else {
                // Jika error lain, lempar errornya agar kita tahu
                throw $e;
            }
        }
    }

    // --- FUNGSI UTILITY ---
    public function batal()
    {
        $this->reset(['banjar_id', 'nama_banjar']);
        $this->resetValidation();
    }

    public function with()
    {
        return [
            'banjars' => Banjar::where('nama_banjar', 'like', '%' . $this->search . '%')
                               ->orderBy('nama_banjar', 'asc')
                               ->paginate(10)
        ];
    }
};
?>

<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl">Master Banjar</flux:heading>
            <flux:subheading>Kelola data wilayah banjar adat atau dinas.</flux:subheading>
        </div>
        
        <div class="flex w-full md:w-auto items-center gap-2">
            <!-- Kotak Pencarian -->
            <flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="Cari nama banjar..." class="w-full md:w-64" />
            
            <flux:button variant="primary" icon="plus" x-on:click="$flux.modal('tambah-banjar').show()">
                Tambah Banjar
            </flux:button>
        </div>
    </div>

    <!-- Tabel dengan efek loading -->
    <flux:card class="relative">
        
        <!-- Indikator Loading -->
        <div wire:loading wire:target="search, gotoPage, nextPage, previousPage" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
            <div class="flex items-center gap-3 px-5 py-2.5 bg-white dark:bg-zinc-800 shadow-lg rounded-full border border-zinc-200 dark:border-zinc-700">
                <svg class="w-5 h-5 animate-spin text-zinc-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Memuat...</span>
            </div>
        </div>

        <div wire:loading.class="opacity-40 pointer-events-none transition-opacity duration-200" wire:target="search, gotoPage, nextPage, previousPage">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Nama Banjar</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($banjars as $banjar)
                    <flux:table.row>
                        <flux:table.cell class="font-semibold">{{ $banjar->nama_banjar }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button wire:click="edit({{ $banjar->id }})" size="sm" variant="ghost" icon="pencil-square" />
                            <flux:button wire:click="konfirmasiHapus({{ $banjar->id }})" size="sm" variant="ghost" color="danger" icon="trash" />
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="2" class="text-center text-zinc-500 py-4">Data banjar tidak ditemukan.</flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            
            <!-- Link Pagination -->
            <div class="mt-4">
                {{ $banjars->links() }}
            </div>
        </div>
    </flux:card>

    <flux:modal name="tambah-banjar" class="min-w-[400px]" wire:close="batal">
        <form wire:submit="simpan">
            <div class="mb-4">
                <flux:heading size="lg">Tambah Banjar Baru</flux:heading>
            </div>
            <flux:input wire:model="nama_banjar" label="Nama Banjar" placeholder="Contoh: Br. Tengah" />
            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-banjar" class="min-w-[400px]" wire:close="batal">
        <form wire:submit="update">
            <div class="mb-4">
                <flux:heading size="lg">Edit Banjar</flux:heading>
            </div>
            <flux:input wire:model="nama_banjar" label="Nama Banjar" />
            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Update Data</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="hapus-banjar" class="min-w-[400px]" wire:close="batal">
        <div class="mb-4">
            <flux:heading size="lg">Hapus Banjar?</flux:heading>
            <flux:subheading>Tindakan ini tidak dapat dibatalkan. Apakah Anda yakin ingin menghapus data banjar ini?</flux:subheading>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
            <flux:button wire:click="destroy" variant="danger">Ya, Hapus</flux:button>
        </div>
    </flux:modal>
</div>