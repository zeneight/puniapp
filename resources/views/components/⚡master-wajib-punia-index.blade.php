<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

use App\Models\WajibPunia;
use App\Models\Banjar;
use App\Models\Pemilik;
use App\Models\JenisUsaha;

use App\Models\DokumenWajibPunia;
use Intervention\Image\ImageManager; 
// use Intervention\Image\Drivers\Gd\Driver;

new class extends Component {
	use WithPagination, WithFileUploads;

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

	// Variabel Upload Dokumen
	public $dokumens = []; // Untuk menampung file baru
	public $dokumenLama = []; // Untuk menampilkan file yang sudah ada di database saat edit

	// --- TAMBAHAN VARIABEL PREVIEW ---
	public string $previewUrl = '';
	public bool $previewIsPdf = false;

	public string $search = '';

	public function updatingSearch()
	{
		$this->resetPage();
	}

	protected function rules()
	{
		return [
			'nama' => 'required|string|min:3|max:150',
			'no_registrasi' => 'required|nullable|string|unique:wajib_punias,no_registrasi,' . $this->wajib_punia_id,
			'tgl_registrasi' => 'required|nullable|date',
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

		// Panggil mesin kompresi & upload
		$this->prosesUpload($wp->id);
		
		$this->batal();
		$this->js('$flux.modal("tambah-wp").close()');
		\Flux::toast('Data Wajib Punia berhasil ditambahkan.', variant: 'success');
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

		// Tarik data dokumen lama untuk ditampilkan preview-nya
		$this->dokumenLama = $wp->dokumens;
		
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

		// Eksekusi upload jika ada file baru yang ditambahkan saat edit
		$this->prosesUpload($wp->id);
		
		$this->batal();
		$this->js('$flux.modal("edit-wp").close()');
		\Flux::toast('Data Wajib Punia berhasil diperbarui.', variant: 'success');
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
		\Flux::toast('Data beserta dokumen berhasil dihapus.', variant: 'success');
	}

	// --- FUNGSI UTILITY ---
	public function batal()
	{
		$this->reset([
			'wajib_punia_id', 'nama', 'no_registrasi', 'tgl_registrasi', 
			'pagu_dudukan', 'pemilik_id', 'jenis_usaha_id', 'banjar_id', 
			'alamat', 'dokumens', 'dokumenLama'
		]);

		$this->pemilik_id = '';
		$this->jenis_usaha_id = '';
		$this->banjar_id = '';

		$this->is_active = true;
		$this->resetValidation();
	}

	private function prosesUpload($wajib_punia_id)
	{
		if (!empty($this->dokumens)) {
			$manager = \Intervention\Image\ImageManager::usingDriver(
				\Intervention\Image\Drivers\Gd\Driver::class
			);
			
			// Bikin struktur folder dinamis: dokumen_punia/TAHUN/BULAN
			$yearMonth = date('Y/m'); 
			$baseFolder = 'dokumen_punia/' . $yearMonth;
			$storagePath = storage_path('app/public/' . $baseFolder);

			if (!file_exists($storagePath)) {
				mkdir($storagePath, 0755, true);
			}
			
			foreach ($this->dokumens as $file) {
				$extension = strtolower($file->getClientOriginalExtension());
				$originalName = $file->getClientOriginalName();
				$isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp']);
				
				// Format nama file
				$filename = uniqid() . '_' . time() . '.' . ($isImage ? 'jpg' : $extension);
				$dbPath = $baseFolder . '/' . $filename;
				
				if ($isImage) {
					$image = $manager->decode($file->getRealPath());
					$image->scaleDown(width: 1080);
					$encoded = $image->encodeUsingFormat(\Intervention\Image\Format::JPEG, quality: 70);
					$encoded->save($storagePath . '/' . $filename);
				} else {
					$file->storeAs($baseFolder, $filename, 'public');
				}

				\App\Models\DokumenWajibPunia::create([
					'wajib_punia_id' => $wajib_punia_id,
					'nama_file' => $originalName,
					'path_file' => $dbPath,
				]);
			}
		}
	}

	// --- FUNGSI PREVIEW POPUP ---
	public function lihatDokumen($id)
    {
        // Cari dokumen berdasarkan ID yang diklik
        $dokumen = \App\Models\DokumenWajibPunia::findOrFail($id);
        
        // Racik URL dan tentukan apakah PDF menggunakan PHP
        $this->previewUrl = asset('storage/' . $dokumen->path_file);
        $this->previewIsPdf = str_ends_with(strtolower($dokumen->path_file), '.pdf');
        
        // Buka modal preview
        $this->js('$flux.modal("preview-dokumen").show()');
    }

	// --- FUNGSI HAPUS DOKUMEN ---
	public function hapusDokumen($id)
	{
		$dokumen = \App\Models\DokumenWajibPunia::findOrFail($id);
		
		// Hapus file fisik dari storage
		if (\Illuminate\Support\Facades\Storage::disk('public')->exists($dokumen->path_file)) {
			\Illuminate\Support\Facades\Storage::disk('public')->delete($dokumen->path_file);
		}
		
		// Hapus data dari database
		$dokumen->delete();

		// Refresh tampilan thumbnail di modal
		$wp = WajibPunia::find($this->wajib_punia_id);
		$this->dokumenLama = $wp ? $wp->dokumens : [];
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
								<flux:badge color="green" size="sm" inset="top bottom">Aktif</flux:badge>
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

				<div class="md:col-span-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:input wire:model="dokumens" type="file" label="Lampiran Dokumen (Opsional)" multiple accept="image/*,application/pdf" />
                    <div class="text-[11px] text-zinc-500 mt-1">Bisa pilih file Gambar (otomatis dikompres) atau Dokumen PDF.</div>
                    
                    <div wire:loading wire:target="dokumens" class="mt-3">
                        <div class="flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400 font-medium bg-indigo-50 dark:bg-indigo-500/10 px-3 py-2 rounded-lg inline-flex">
                            <flux:icon.arrow-path class="w-4 h-4 animate-spin" /> Sedang memproses file ke memori...
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap gap-3 mt-3">
                        @if (!empty($dokumenLama))
                            @foreach ($dokumenLama as $lama)
                                @php $isPdf = str_ends_with(strtolower($lama->path_file), '.pdf'); @endphp
                                
                                <div wire:key="dokumen-{{ $lama->id }}" class="relative group w-16 h-16 rounded-md overflow-hidden border border-zinc-200 dark:border-zinc-700 bg-zinc-50">
                                    
                                    <div wire:loading wire:target="lihatDokumen({{ $lama->id }})" class="absolute inset-0 bg-white/80 dark:bg-zinc-800/80 flex items-center justify-center z-20 backdrop-blur-sm">
                                        <flux:icon.arrow-path class="w-5 h-5 animate-spin text-indigo-600" />
                                    </div>

                                    <button type="button" wire:click.prevent="hapusDokumen({{ $lama->id }})" wire:confirm="Yakin ingin menghapus dokumen ini?" class="absolute top-0 right-0 bg-red-500 text-white rounded-bl-md p-1 opacity-0 group-hover:opacity-100 transition-opacity z-10 hover:bg-red-600">
                                        <flux:icon.x-mark class="w-3 h-3" stroke-width="3" />
                                    </button>

                                    <div wire:click.stop="lihatDokumen({{ $lama->id }})" class="w-full h-full flex items-center justify-center opacity-70 hover:opacity-100 transition-opacity cursor-pointer" title="Lihat: {{ $lama->nama_file }}">
                                        @if($isPdf)
                                            <flux:icon.document-text class="w-8 h-8 text-red-500" />
                                        @else
                                            <img src="{{ asset('storage/' . $lama->path_file) }}" class="object-cover w-full h-full" alt="Dokumen">
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        @if ($dokumens)
                            @foreach ($dokumens as $dok)
                                <div class="relative w-16 h-16 rounded-md overflow-hidden border border-indigo-500 shadow-sm shadow-indigo-200 bg-indigo-50 flex items-center justify-center cursor-help" title="Belum tersimpan: {{ $dok->getClientOriginalName() }}">
                                    @if(in_array(strtolower($dok->getClientOriginalExtension()), ['pdf']))
                                        <flux:icon.document-text class="w-8 h-8 text-red-500" />
                                    @else
                                        <img src="{{ $dok->temporaryUrl() }}" class="object-cover w-full h-full" alt="Preview">
                                    @endif
                                    <div class="absolute inset-0 bg-indigo-500/10 border-2 border-indigo-500 border-dashed rounded-md"></div>
                                </div>
                            @endforeach
                        @endif
                    </div>
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

				<div class="md:col-span-2 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:input wire:model="dokumens" type="file" label="Lampiran Dokumen (Opsional)" multiple accept="image/*,application/pdf" />
                    <div class="text-[11px] text-zinc-500 mt-1">Bisa pilih file Gambar (otomatis dikompres) atau Dokumen PDF.</div>
                    
                    <div wire:loading wire:target="dokumens" class="mt-3">
                        <div class="flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400 font-medium bg-indigo-50 dark:bg-indigo-500/10 px-3 py-2 rounded-lg inline-flex">
                            <flux:icon.arrow-path class="w-4 h-4 animate-spin" /> Sedang memproses file ke memori...
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap gap-3 mt-3">
                        @if (!empty($dokumenLama))
                            @foreach ($dokumenLama as $lama)
                                @php $isPdf = str_ends_with(strtolower($lama->path_file), '.pdf'); @endphp
                                
                                <div wire:key="dokumen-{{ $lama->id }}" class="relative group w-16 h-16 rounded-md overflow-hidden border border-zinc-200 dark:border-zinc-700 bg-zinc-50">
                                    
                                    <div wire:loading wire:target="lihatDokumen({{ $lama->id }})" class="absolute inset-0 bg-white/80 dark:bg-zinc-800/80 flex items-center justify-center z-20 backdrop-blur-sm">
                                        <flux:icon.arrow-path class="w-5 h-5 animate-spin text-indigo-600" />
                                    </div>

                                    <button type="button" wire:click.prevent="hapusDokumen({{ $lama->id }})" wire:confirm="Yakin ingin menghapus dokumen ini?" class="absolute top-0 right-0 bg-red-500 text-white rounded-bl-md p-1 opacity-0 group-hover:opacity-100 transition-opacity z-10 hover:bg-red-600">
                                        <flux:icon.x-mark class="w-3 h-3" stroke-width="3" />
                                    </button>

                                    <div wire:click.stop="lihatDokumen({{ $lama->id }})" class="w-full h-full flex items-center justify-center opacity-70 hover:opacity-100 transition-opacity cursor-pointer" title="Lihat: {{ $lama->nama_file }}">
                                        @if($isPdf)
                                            <flux:icon.document-text class="w-8 h-8 text-red-500" />
                                        @else
                                            <img src="{{ asset('storage/' . $lama->path_file) }}" class="object-cover w-full h-full" alt="Dokumen">
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        @if ($dokumens)
                            @foreach ($dokumens as $dok)
                                <div class="relative w-16 h-16 rounded-md overflow-hidden border border-indigo-500 shadow-sm shadow-indigo-200 bg-indigo-50 flex items-center justify-center cursor-help" title="Belum tersimpan: {{ $dok->getClientOriginalName() }}">
                                    @if(in_array(strtolower($dok->getClientOriginalExtension()), ['pdf']))
                                        <flux:icon.document-text class="w-8 h-8 text-red-500" />
                                    @else
                                        <img src="{{ $dok->temporaryUrl() }}" class="object-cover w-full h-full" alt="Preview">
                                    @endif
                                    <div class="absolute inset-0 bg-indigo-500/10 border-2 border-indigo-500 border-dashed rounded-md"></div>
                                </div>
                            @endforeach
                        @endif
                    </div>
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

	<flux:modal name="preview-dokumen" class="md:w-[800px] h-[85vh] flex flex-col p-0 overflow-hidden">
        
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 pr-12">
            <flux:heading size="lg">Detail Dokumen</flux:heading>
        </div>
        
        <div class="flex-1 bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center overflow-auto p-4 relative">
            
            <div wire:loading wire:target="lihatDokumen" class="absolute inset-0 bg-zinc-100/80 dark:bg-zinc-900/80 backdrop-blur-sm flex items-center justify-center z-10">
                <div class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-zinc-800 rounded-full shadow-md">
                    <flux:icon.arrow-path class="w-5 h-5 animate-spin text-indigo-500" />
                    <span class="text-sm font-medium">Memuat dokumen...</span>
                </div>
            </div>

            @if($previewUrl)
                @if($previewIsPdf)
                    <iframe src="{{ $previewUrl }}" class="w-full h-full border-0 shadow-lg bg-white rounded-md relative z-0"></iframe>
                @else
                    <img 
                        src="{{ $previewUrl }}" 
                        onerror="this.onerror=null; this.src='https://placehold.co/600x400/ef4444/ffffff?text=File+Fisik+Hilang/Tidak+Ditemukan';" 
                        class="max-w-full max-h-full object-contain shadow-lg rounded-md relative z-0" 
                        alt="Preview Resolusi Penuh" 
                    />
                @endif
            @endif
        </div>
    </flux:modal>
</div>