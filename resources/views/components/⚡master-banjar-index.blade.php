<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Banjar;

new class extends Component {
    #[Layout('layouts.app')] 
    
    // Variabel untuk menampung inputan dan ID data yang dipilih
    public ?int $banjar_id = null;
    public string $nama_banjar = '';

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
    }

    // --- FUNGSI DELETE ---
    public function konfirmasiHapus($id)
    {
        $this->banjar_id = $id;
        $this->js('$flux.modal("hapus-banjar").show()');
    }

    public function destroy()
    {
        Banjar::findOrFail($this->banjar_id)->delete();
        $this->batal();
        $this->js('$flux.modal("hapus-banjar").close()');
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
            'banjars' => Banjar::latest()->get()
        ];
    }
};
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <flux:heading size="xl">Master Banjar</flux:heading>
            <flux:subheading>Kelola daftar banjar untuk pemetaan wilayah wajib punia.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" x-on:click="$flux.modal('tambah-banjar').show()">
            Tambah Banjar
        </flux:button>
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>No</flux:table.column>
                <flux:table.column>Nama Banjar</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($banjars as $index => $banjar)
                <flux:table.row>
                    <flux:table.cell>{{ $index + 1 }}</flux:table.cell>
                    <flux:table.cell>{{ $banjar->nama_banjar }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button wire:click="edit({{ $banjar->id }})" size="sm" variant="ghost" icon="pencil-square">Edit</flux:button>
                        <flux:button wire:click="konfirmasiHapus({{ $banjar->id }})" size="sm" variant="ghost" color="danger" icon="trash">Hapus</flux:button>
                    </flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
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