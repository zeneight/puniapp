<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\WajibPunia;
use App\Models\Banjar;
use App\Models\Kategori;

new class extends Component {
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

    protected function rules()
    {
        return [
            'nama' => 'required|min:3',
            'alamat' => 'required',
            'banjar_id' => 'required|exists:banjars,id',
            'kategori_id' => 'required|exists:kategoris,id',
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
        return [
            'wajibPunias' => WajibPunia::with(['banjar', 'kategori'])->latest()->get(),
            'banjars' => Banjar::all(),
            'kategoris' => Kategori::all(),
        ];
    }
};
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <flux:heading size="xl">Data Master Wajib Punia</flux:heading>
            <flux:subheading>Daftar seluruh tempat usaha, proyek, atau domisili yang ditarik punia dudukan.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" x-on:click="$flux.modal('tambah-wp').show()">
            Registrasi Wajib Punia
        </flux:button>
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Nama / Usaha</flux:table.column>
                <flux:table.column>Banjar</flux:table.column>
                <flux:table.column>Kategori</flux:table.column>
                <flux:table.column>Pagu Bulanan</flux:table.column>
                <flux:table.column>Kontak</flux:table.column>
                <flux:table.column>Aksi</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($wajibPunias as $wp)
                <flux:table.row>
                    <flux:table.cell>
                        <div class="font-semibold text-zinc-800 dark:text-white">{{ $wp->nama }}</div>
                        <div class="text-xs text-zinc-500">{{ $wp->alamat }}</div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $wp->banjar->nama_banjar }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" inset="top bottom">{{ $wp->kategori->nama_kategori }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="font-mono">Rp {{ number_format($wp->pagu_dudukan, 0, ',', '.') }}</flux:table.cell>
                    <flux:table.cell>{{ $wp->kontak_pengelola ?? '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button wire:click="edit({{ $wp->id }})" size="sm" variant="ghost" icon="pencil-square" />
                        <flux:button wire:click="konfirmasiHapus({{ $wp->id }})" size="sm" variant="ghost" color="danger" icon="trash" />
                    </flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
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