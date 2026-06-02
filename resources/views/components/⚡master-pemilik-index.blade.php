<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Pemilik;

new class extends Component {
    use WithPagination;

    #[Layout('layouts.app')]

    public ?int $pemilik_id = null;
    public string $nama_pemilik = '';
    public string $asal_pemilik = '';
    public string $no_telp = '';
    public string $nik = '';
    
    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    protected function rules()
    {
        return [
            'nama_pemilik' => 'required|string|min:3|max:150',
            'asal_pemilik' => 'nullable|string|max:100',
            'no_telp' => 'nullable|string|max:20',
            'nik' => 'nullable|string|max:20|unique:pemiliks,nik,' . $this->pemilik_id,
        ];
    }

    // --- FUNGSI CREATE ---
    public function simpan()
    {
        $this->validate();
        Pemilik::create([
            'nama_pemilik' => $this->nama_pemilik,
            'asal_pemilik' => $this->asal_pemilik,
            'no_telp' => $this->no_telp,
            'nik' => $this->nik,
        ]);
        $this->batal();
        $this->js('$flux.modal("tambah-pemilik").close()');
    }

    // --- FUNGSI UPDATE ---
    public function edit($id)
    {
        $p = Pemilik::findOrFail($id);
        $this->pemilik_id = $p->id;
        $this->nama_pemilik = $p->nama_pemilik;
        $this->asal_pemilik = $p->asal_pemilik ?? '';
        $this->no_telp = $p->no_telp ?? '';
        $this->nik = $p->nik ?? '';
        
        $this->resetValidation();
        $this->js('$flux.modal("edit-pemilik").show()');
    }

    public function update()
    {
        $this->validate();
        
        $p = Pemilik::findOrFail($this->pemilik_id);
        $p->update([
            'nama_pemilik' => $this->nama_pemilik,
            'asal_pemilik' => $this->asal_pemilik,
            'no_telp' => $this->no_telp,
            'nik' => $this->nik,
        ]);
        
        $this->batal();
        $this->js('$flux.modal("edit-pemilik").close()');
    }

    // --- FUNGSI DELETE ---
    public function konfirmasiHapus($id)
    {
        $this->pemilik_id = $id;
        $this->js('$flux.modal("hapus-pemilik").show()');
    }

    public function destroy()
    {
        Pemilik::findOrFail($this->pemilik_id)->delete();
        $this->batal();
        $this->js('$flux.modal("hapus-pemilik").close()');
    }

    // --- FUNGSI UTILITY ---
    public function batal()
    {
        $this->reset(['pemilik_id', 'nama_pemilik', 'asal_pemilik', 'no_telp', 'nik']);
        $this->resetValidation();
    }

    public function with()
    {
        return [
            'pemiliks' => Pemilik::where('nama_pemilik', 'like', '%' . $this->search . '%')
                                 ->orWhere('nik', 'like', '%' . $this->search . '%')
                                 ->orderBy('nama_pemilik', 'asc')
                                 ->paginate(10)
        ];
    }
};
?>

<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl">Master Pemilik / Donatur</flux:heading>
            <flux:subheading>Kelola data profil pemilik usaha penanggung jawab Punia.</flux:subheading>
        </div>
        
        <div class="flex w-full md:w-auto items-center gap-2">
            <flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="Cari nama atau NIK..." class="w-full md:w-64" />
            <flux:button variant="primary" icon="plus" x-on:click="$flux.modal('tambah-pemilik').show()">
                Tambah Pemilik
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
                    <flux:table.column>Nama Pemilik</flux:table.column>
                    <flux:table.column>Asal / Alamat</flux:table.column>
                    <flux:table.column>Kontak</flux:table.column>
                    <flux:table.column>Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($pemiliks as $p)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="font-semibold">{{ $p->nama_pemilik }}</div>
                            <div class="text-[10px] text-zinc-400 font-mono">NIK: {{ $p->nik ?? '-' }}</div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $p->asal_pemilik ?? '-' }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-sm">{{ $p->no_telp ?? '-' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button wire:click="edit({{ $p->id }})" size="sm" variant="ghost" icon="pencil-square" />
                            <flux:button wire:click="konfirmasiHapus({{ $p->id }})" size="sm" variant="ghost" color="danger" icon="trash" />
                        </flux:table.cell>
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center text-zinc-500 py-4">Data tidak ditemukan.</flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            
            <div class="mt-4">
                {{ $pemiliks->links() }}
            </div>
        </div>
    </flux:card>

    <flux:modal name="tambah-pemilik" class="md:w-[550px]" wire:close="batal">
        <form wire:submit="simpan" class="space-y-6">
            <div>
                <flux:heading size="lg">Registrasi Pemilik Baru</flux:heading>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="nama_pemilik" label="Nama Lengkap" />
                <flux:input wire:model="nik" label="NIK (No. KTP)" />
            </div>
            <flux:input wire:model="asal_pemilik" label="Asal / Alamat Rumah" />
            <flux:input wire:model="no_telp" label="No. Telepon / WhatsApp" />
            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-pemilik" class="md:w-[550px]" wire:close="batal">
        <form wire:submit="update" class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Profil Pemilik</flux:heading>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="nama_pemilik" label="Nama Lengkap" />
                <flux:input wire:model="nik" label="NIK (No. KTP)" />
            </div>
            <flux:input wire:model="asal_pemilik" label="Asal / Alamat Rumah" />
            <flux:input wire:model="no_telp" label="No. Telepon / WhatsApp" />
            <div class="flex justify-end gap-2 mt-6">
                <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Update Data</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="hapus-pemilik" class="min-w-[400px]" wire:close="batal">
        <div class="mb-4">
            <flux:heading size="lg">Hapus Pemilik?</flux:heading>
            <flux:subheading>Tindakan ini tidak dapat dibatalkan.</flux:subheading>
        </div>
        <div class="flex justify-end gap-2 mt-6">
            <flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
            <flux:button wire:click="destroy" variant="danger">Ya, Hapus</flux:button>
        </div>
    </flux:modal>
</div>