<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Kategori;

new class extends Component {
    use WithPagination;

    #[Layout('layouts.app')]

    public ?int $kategori_id = null;
    public string $nama_kategori = '';
    public string $deskripsi = '';
    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    protected function rules()
    {
        return [
            'nama_kategori' => 'required|string|min:3|max:100',
            'deskripsi' => 'nullable|string|max:255',
        ];
    }

    // --- FUNGSI CREATE ---
    public function simpan()
    {
        $this->validate();
        Kategori::create([
            'nama_kategori' => $this->nama_kategori,
            'deskripsi' => $this->deskripsi
        ]);
        $this->batal();
        \Flux::toast('Data berhasil ditambahkan.', variant: 'success');
        $this->js('$flux.modal("tambah-kategori").close()');
    }

    // --- FUNGSI UPDATE ---
    public function edit($id)
    {
        $kategori = Kategori::findOrFail($id);
        $this->kategori_id = $kategori->id;
        $this->nama_kategori = $kategori->nama_kategori;
        $this->deskripsi = $kategori->deskripsi;

        $this->resetValidation();
        $this->js('$flux.modal("edit-kategori").show()');
    }

    public function update()
    {
        $this->validate();

        $kategori = Kategori::findOrFail($this->kategori_id);
        $kategori->update([
            'nama_kategori' => $this->nama_kategori,
            'deskripsi' => $this->deskripsi
        ]);
        
        $this->batal();
        $this->js('$flux.modal("edit-kategori").close()');
        \Flux::toast('Data berhasil diperbarui.', variant: 'success');
    }

    // --- FUNGSI DELETE ---
    public function konfirmasiHapus($id)
    {
        // Hitung jumlah data Wajib Punia yang terhubung ke Kategori ini
        // (Pastikan relasi 'wajibPunias' sudah ada di model Kategori.php)
        $kategori = Kategori::withCount('wajibPunias')->findOrFail($id);
        
        if ($kategori->wajib_punias_count > 0) {
            \Flux::toast('Aksi ditolak! Kategori ini masih digunakan oleh ' . $kategori->wajib_punias_count . ' data Wajib Punia.', variant: 'danger');
            return; // Hentikan proses, modal hapus tidak akan terbuka
        }

        $this->kategori_id = $id;
        $this->js('$flux.modal("hapus-kategori").show()');
    }

    public function destroy()
    {
        try {
            Kategori::findOrFail($this->kategori_id)->delete();
            
            $this->batal(); // Reset form
            $this->js('$flux.modal("hapus-kategori").close()');
            \Flux::toast('Kategori berhasil dihapus.', variant: 'success');
            
        } catch (\Illuminate\Database\QueryException $e) {
            // Jika error code-nya 23000 (Integrity Constraint / Foreign Key Fail)
            if ($e->getCode() == 23000) {
                $this->js('$flux.modal("hapus-kategori").close()');
                \Flux::toast('Gagal menghapus! Data Kategori masih terikat dengan data lain di sistem.', variant: 'danger');
            } else {
                // Jika error lain, lempar errornya agar kita tahu
                throw $e;
            }
        }
    }

    // --- FUNGSI UTILITY ---
    public function batal()
    {
        $this->reset(['kategori_id', 'nama_kategori', 'deskripsi']);
        $this->resetValidation();
    }

    public function with()
    {
        return [
            'kategoris' => Kategori::where('nama_kategori', 'like', '%' . $this->search . '%')
                                      ->orderBy('nama_kategori', 'asc')
                                      ->paginate(10)
        ];
    }
};
?>

<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl">Master Kategori</flux:heading>
            <flux:subheading>Kelola kategori dudukan Wajib Punia.</flux:subheading>
        </div>
        
        <div class="flex w-full md:w-auto items-center gap-2">
            <flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="Cari kategori..." class="w-full md:w-64" />
            <flux:button variant="primary" icon="plus" x-on:click="$flux.modal('tambah-kategori').show()">
                Tambah
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
                    <flux:table.column>Kategori</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($kategoris as $kategori)
                    <flux:table.row>
                        <flux:table.cell class="font-semibold">{{ $kategori->nama_kategori }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button wire:click="edit({{ $kategori->id }})" size="sm" variant="ghost" icon="pencil-square" />
                            <flux:button wire:click="konfirmasiHapus({{ $kategori->id }})" size="sm" variant="ghost" color="danger" icon="trash" />
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="2" class="text-center text-zinc-500 py-4">Data tidak ditemukan.</flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            
            <div class="mt-4">
                {{ $kategoris->links() }}
            </div>
        </div>
    </flux:card>

    <flux:modal name="tambah-kategori" class="min-w-[400px]" wire:close="batal">
        <form wire:submit="simpan">
            <div class="mb-4">
                <flux:heading size="lg">Tambah Kategori</flux:heading>
            </div>
            <flux:input wire:model="nama_kategori" label="Nama Kategori" placeholder="Contoh: Usaha" />
            <div class="md:col-span-2 mt-4">
                <flux:textarea wire:model="deskripsi" label="Deskripsi Kategori (Opsional)" rows="2" placeholder="Contoh: Kategori untuk villa dan toko" />
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-kategori" class="min-w-[400px]" wire:close="batal">
        <form wire:submit="update">
            <div class="mb-4">
                <flux:heading size="lg">Edit Kategori</flux:heading>
            </div>
            <flux:input wire:model="nama_kategori" label="Nama Kategori" />
            <div class="md:col-span-2 mt-4">
                <flux:textarea wire:model="deskripsi" label="Deskripsi Kategori (Opsional)" rows="2" placeholder="Contoh: Kategori untuk villa dan toko" />
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Update Data</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="hapus-kategori" class="min-w-[400px]" wire:close="batal">
        <div class="mb-4">
            <flux:heading size="lg">Hapus Kategori?</flux:heading>
            <flux:subheading>Tindakan ini tidak dapat dibatalkan.</flux:subheading>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
            <flux:button wire:click="destroy" variant="danger">Ya, Hapus</flux:button>
        </div>
    </flux:modal>
</div>