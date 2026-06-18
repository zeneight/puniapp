<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\JenisUsaha;

new class extends Component {
    use WithPagination;

    #[Layout('layouts.app')]

    public ?int $jenis_usaha_id = null;
    public string $nama_jenis_usaha = '';
    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    protected function rules()
    {
        return [
            'nama_jenis_usaha' => 'required|string|min:3|max:100',
        ];
    }

    // --- FUNGSI CREATE ---
    public function simpan()
    {
        $this->validate();
        JenisUsaha::create(['nama_jenis_usaha' => $this->nama_jenis_usaha]);
        $this->batal();
        $this->js('$flux.modal("tambah-jenis-usaha").close()');
    }

    // --- FUNGSI UPDATE ---
    public function edit($id)
    {
        $ju = JenisUsaha::findOrFail($id);
        $this->jenis_usaha_id = $ju->id;
        $this->nama_jenis_usaha = $ju->nama_jenis_usaha;
        
        $this->resetValidation();
        $this->js('$flux.modal("edit-jenis-usaha").show()');
    }

    public function update()
    {
        $this->validate();
        
        $ju = JenisUsaha::findOrFail($this->jenis_usaha_id);
        $ju->update(['nama_jenis_usaha' => $this->nama_jenis_usaha]);
        
        $this->batal();
        $this->js('$flux.modal("edit-jenis-usaha").close()');
    }

    // --- FUNGSI DELETE ---
    public function konfirmasiHapus($id)
    {
        // Hitung jumlah data Wajib Punia yang terhubung ke Jenis Usaha ini
        // (Pastikan relasi 'wajibPunias' sudah ada di model JenisUsaha.php)
        $jenis_usaha = JenisUsaha::withCount('wajibPunias')->findOrFail($id);
        
        if ($jenis_usaha->wajib_punias_count > 0) {
            \Flux::toast('Aksi ditolak! Jenis Usaha ini masih digunakan oleh ' . $jenis_usaha->wajib_punias_count . ' data Wajib Punia.', variant: 'danger');
            return; // Hentikan proses, modal hapus tidak akan terbuka
        }

        $this->jenis_usaha_id = $id;
        $this->js('$flux.modal("hapus-jenis-usaha").show()');
    }

    public function destroy()
    {
        try {
            JenisUsaha::findOrFail($this->jenis_usaha_id)->delete();
            
            $this->batal(); // Reset form
            $this->js('$flux.modal("hapus-jenis-usaha").close()');
            \Flux::toast('Jenis Usaha berhasil dihapus.', variant: 'success');
            
        } catch (\Illuminate\Database\QueryException $e) {
            // Jika error code-nya 23000 (Integrity Constraint / Foreign Key Fail)
            if ($e->getCode() == 23000) {
                $this->js('$flux.modal("hapus-jenis-usaha").close()');
                \Flux::toast('Gagal menghapus! Data Jenis Usaha masih terikat dengan data lain di sistem.', variant: 'danger');
            } else {
                // Jika error lain, lempar errornya agar kita tahu
                throw $e;
            }
        }
    }

    // --- FUNGSI UTILITY ---
    public function batal()
    {
        $this->reset(['jenis_usaha_id', 'nama_jenis_usaha']);
        $this->resetValidation();
    }

    public function with()
    {
        return [
            'jenisUsahas' => JenisUsaha::where('nama_jenis_usaha', 'like', '%' . $this->search . '%')
                                       ->orderBy('nama_jenis_usaha', 'asc')
                                       ->paginate(10)
        ];
    }
};
?>

<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl">Master Jenis Usaha</flux:heading>
            <flux:subheading>Kelola kategori bidang usaha Wajib Punia.</flux:subheading>
        </div>
        
        <div class="flex w-full md:w-auto items-center gap-2">
            <flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="Cari jenis usaha..." class="w-full md:w-64" />
            <flux:button variant="primary" icon="plus" x-on:click="$flux.modal('tambah-jenis-usaha').show()">
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
                    <flux:table.column>Jenis Usaha</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($jenisUsahas as $ju)
                    <flux:table.row>
                        <flux:table.cell class="font-semibold">{{ $ju->nama_jenis_usaha }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button wire:click="edit({{ $ju->id }})" size="sm" variant="ghost" icon="pencil-square" />
                            <flux:button wire:click="konfirmasiHapus({{ $ju->id }})" size="sm" variant="ghost" color="danger" icon="trash" />
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
                {{ $jenisUsahas->links() }}
            </div>
        </div>
    </flux:card>

    <flux:modal name="tambah-jenis-usaha" class="min-w-[400px]" wire:close="batal">
        <form wire:submit="simpan">
            <div class="mb-4">
                <flux:heading size="lg">Tambah Jenis Usaha</flux:heading>
            </div>
            <flux:input wire:model="nama_jenis_usaha" label="Nama Jenis Usaha" placeholder="Contoh: Villa, Toko" />
            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-jenis-usaha" class="min-w-[400px]" wire:close="batal">
        <form wire:submit="update">
            <div class="mb-4">
                <flux:heading size="lg">Edit Jenis Usaha</flux:heading>
            </div>
            <flux:input wire:model="nama_jenis_usaha" label="Nama Jenis Usaha" />
            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Update Data</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="hapus-jenis-usaha" class="min-w-[400px]" wire:close="batal">
        <div class="mb-4">
            <flux:heading size="lg">Hapus Jenis Usaha?</flux:heading>
            <flux:subheading>Tindakan ini tidak dapat dibatalkan.</flux:subheading>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
            <flux:button wire:click="destroy" variant="danger">Ya, Hapus</flux:button>
        </div>
    </flux:modal>
</div>