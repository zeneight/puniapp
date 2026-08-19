<?php


namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\BukuTamu;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
	use WithPagination;

	#[Layout('layouts.app')]

	// Variabel Filter & Pencarian
	public $search = '';
	public $filter_tanggal = '';
    public $filter_status = '';
    public $filter_prioritas = '';

	// Variabel Form Tambah/Edit
	public $edit_id;
	public $tanggal_kunjungan;
	public $nama_pengunjung;
	public $asal_instansi;
	public $kontak_wa;
	public $pekerjaan_status;
	public $alasan_kunjungan;
	public $tindak_lanjut;

	public $status = 'Tamu masuk';
    public $prioritas = 'Prioritas 3';

	// Variabel Hapus
	public $hapus_id;

	public function mount()
	{
		$this->tanggal_kunjungan = date('Y-m-d'); // Default ke hari ini
	}

	public function updatingSearch() { $this->resetPage(); }
	public function updatingFilterTanggal() { $this->resetPage(); }

	public function resetFilter()
	{
		$this->reset(['search', 'filter_tanggal', 'filter_status', 'filter_prioritas']);
		$this->resetPage();
	}

	public function batal()
	{
		$this->reset([
			'edit_id', 'nama_pengunjung', 'asal_instansi', 'kontak_wa', 
			'pekerjaan_status', 'alasan_kunjungan', 'tindak_lanjut'
		]);
		$this->tanggal_kunjungan = date('Y-m-d');
		$this->resetValidation();
	}

	public function simpan()
	{
		$this->validate([
			'tanggal_kunjungan' => 'required|date',
			'nama_pengunjung' => 'required|string|max:255',
			'kontak_wa' => 'nullable|string|max:20',
			'asal_instansi' => 'nullable|string|max:255',
			'pekerjaan_status' => 'nullable|string|max:255',
			'alasan_kunjungan' => 'required|string',
			'tindak_lanjut' => 'nullable|string',
		]);

		// Fitur Cerdas: Hitung ini kunjungan ke berapa berdasarkan Nomor WA atau Nama
		$kunjunganKe = 1;
		if (!empty($this->kontak_wa)) {
			$pastVisits = BukuTamu::where('kontak_wa', $this->kontak_wa)->count();
			$kunjunganKe = $pastVisits + 1;
		} elseif (!empty($this->nama_pengunjung)) {
			$pastVisits = BukuTamu::where('nama_pengunjung', $this->nama_pengunjung)->count();
			$kunjunganKe = $pastVisits + 1;
		}

		BukuTamu::create([
			'user_id' => Auth::id(),
			'tanggal_kunjungan' => $this->tanggal_kunjungan,
			'nama_pengunjung' => $this->nama_pengunjung,
			'kontak_wa' => $this->kontak_wa,
			'asal_instansi' => $this->asal_instansi,
			'pekerjaan_status' => $this->pekerjaan_status,
			'alasan_kunjungan' => $this->alasan_kunjungan,
			'tindak_lanjut' => $this->tindak_lanjut,
			'kunjungan_ke' => $kunjunganKe,
		]);

		$this->js('$flux.modal("tambah-tamu").close()');
		$this->batal();
		\Flux::toast('Data kunjungan berhasil dicatat!', variant: 'success');
	}

	public function edit($id)
	{
		$tamu = BukuTamu::findOrFail($id);
		
		$this->edit_id = $tamu->id;
		$this->tanggal_kunjungan = $tamu->tanggal_kunjungan;
		$this->nama_pengunjung = $tamu->nama_pengunjung;
		$this->kontak_wa = $tamu->kontak_wa;
		$this->asal_instansi = $tamu->asal_instansi;
		$this->pekerjaan_status = $tamu->pekerjaan_status;
		$this->alasan_kunjungan = $tamu->alasan_kunjungan;
		$this->tindak_lanjut = $tamu->tindak_lanjut;
		$this->status = $tamu->status;
		$this->prioritas = $tamu->prioritas;

		$this->resetValidation();
		$this->js('$flux.modal("edit-tamu").show()');
	}

	public function update()
	{
		$this->validate([
			'tanggal_kunjungan' => 'required|date',
			'nama_pengunjung' => 'required|string|max:255',
			'kontak_wa' => 'nullable|string|max:20',
			'asal_instansi' => 'nullable|string|max:255',
			'pekerjaan_status' => 'nullable|string|max:255',
			'alasan_kunjungan' => 'required|string',
			'tindak_lanjut' => 'nullable|string',
			'status' => 'required|in:Tamu masuk,Proses,Selesai',
			'prioritas' => 'required|in:Prioritas 1,Prioritas 2,Prioritas 3',
		]);

		$tamu = BukuTamu::findOrFail($this->edit_id);
		$tamu->update([
			'tanggal_kunjungan' => $this->tanggal_kunjungan,
			'nama_pengunjung' => $this->nama_pengunjung,
			'kontak_wa' => $this->kontak_wa,
			'asal_instansi' => $this->asal_instansi,
			'pekerjaan_status' => $this->pekerjaan_status,
			'alasan_kunjungan' => $this->alasan_kunjungan,
			'tindak_lanjut' => $this->tindak_lanjut,
			'status' => $this->status,
			'prioritas' => $this->prioritas,
		]);

		$this->js('$flux.modal("edit-tamu").close()');
		$this->batal();
		\Flux::toast('Data kunjungan berhasil diperbarui!', variant: 'success');
	}

	public function konfirmasiHapus($id)
	{
		$this->hapus_id = $id;
		$this->js('$flux.modal("hapus-tamu").show()');
	}

	public function destroy()
	{
		BukuTamu::findOrFail($this->hapus_id)->delete();
		$this->js('$flux.modal("hapus-tamu").close()');
		$this->reset('hapus_id');
		\Flux::toast('Data kunjungan dihapus.', variant: 'success');
	}

	public function with()
	{
		// 1. Hitung total data keseluruhan (sebelum difilter)
        $totalKeseluruhan = \App\Models\BukuTamu::count();

		$query = BukuTamu::with('user')
			->when($this->search, function ($q) {
				$q->where(function ($sub) {
					$sub->where('nama_pengunjung', 'like', '%' . $this->search . '%')
						->orWhere('asal_instansi', 'like', '%' . $this->search . '%')
						->orWhere('alasan_kunjungan', 'like', '%' . $this->search . '%');
				});
			})
			->when($this->filter_tanggal, function ($q) {
				$q->whereDate('tanggal_kunjungan', $this->filter_tanggal);
			})
			->orderBy('created_at', 'desc');
        
        if ($this->filter_status) {
            $query->where('status', $this->filter_status);
        }
        
        if ($this->filter_prioritas) {
            $query->where('prioritas', $this->filter_prioritas);
        }

        // 3. Hitung total data setelah difilter (untuk Counting Info)
        $totalDifilter = $query->count();

		return [
			// 'daftarTamu' => $query->paginate(15),
			'dataBukuTamu' => $query->orderBy('created_at', 'desc')->paginate(10),
            'totalKeseluruhan' => $totalKeseluruhan,
            'totalDifilter' => $totalDifilter,
		];
	}
}
?>

<div>
	<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
		<div>
			<flux:heading size="xl">Buku Tamu Digital</flux:heading>
			<flux:subheading>Catat daftar kunjungan dan keperluan tamu Desa Wisata Munggu.</flux:subheading>
		</div>
		
		<flux:button variant="primary" icon="plus" x-on:click="$flux.modal('tambah-tamu').show()">
			Catat Kunjungan Baru
		</flux:button>
	</div>

	<div class="flex flex-col md:flex-row gap-3 mb-4">
		<flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="Cari nama, instansi, atau keperluan..." class="w-full md:w-96" />
		
		<flux:input wire:model.live="filter_tanggal" type="date" class="w-full md:w-48" />

		<div class="w-full sm:w-48">
			<flux:select wire:model.live="filter_status" placeholder="Semua Status">
				<option value="">Semua Status</option>
				<option value="Tamu masuk">Tamu masuk</option>
				<option value="Proses">Proses</option>
				<option value="Selesai">Selesai</option>
			</flux:select>
		</div>
		
		<div class="w-full sm:w-48">
			<flux:select wire:model.live="filter_prioritas" placeholder="Semua Prioritas">
				<option value="">Semua Prioritas</option>
				<option value="Prioritas 1">Prioritas 1</option>
				<option value="Prioritas 2">Prioritas 2</option>
				<option value="Prioritas 3">Prioritas 3</option>
			</flux:select>
		</div>

		@if($search || $filter_tanggal || $filter_status || $filter_prioritas)
			<flux:button wire:click="resetFilter" variant="subtle" icon="x-mark" class="px-3">Reset</flux:button>
		@endif
	</div>

	<flux:card class="relative">
		<div wire:loading wire:target="search, filter_tanggal, gotoPage" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
			<flux:icon.arrow-path class="w-6 h-6 animate-spin text-indigo-500" />
		</div>

		<div wire:loading.class="opacity-40" wire:target="search, filter_tanggal, gotoPage">
			<!-- BAGIAN 1: STATISTIK & FILTER -->
			<div class="mb-6 space-y-4">
				<!-- Informasi Counting Data -->
				<div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-800/50 p-2 rounded-lg border border-zinc-200 dark:border-zinc-700 w-fit">
					<flux:icon.chart-bar class="w-4 h-4" />
					<span>
						Menampilkan <strong class="text-zinc-900 dark:text-zinc-100">{{ $totalDifilter }}</strong> data
						@if($search || $filter_status || $filter_prioritas)
							(dari total {{ $totalKeseluruhan }} data)
						@endif
					</span>
				</div>

			</div>

			<!-- BAGIAN 2: TABEL DENGAN NOMOR URUT -->
			<flux:table>
				<flux:table.columns>
					<flux:table.column>No.</flux:table.column>
					<flux:table.column>Tanggal</flux:table.column>
					<flux:table.column>Info Tamu</flux:table.column>
					<flux:table.column>Keterangan & Keperluan</flux:table.column>
					<flux:table.column>Prioritas</flux:table.column>
					<flux:table.column>Status</flux:table.column>
					<flux:table.column>Aksi</flux:table.column>
				</flux:table.columns>

				<flux:table.rows>
					@forelse ($dataBukuTamu as $index => $tamu)
						<flux:table.row>
							<!-- Kolom No Urut (Akurat meski pindah halaman pagination) -->
							<flux:table.cell class="font-medium text-zinc-500">
								{{ $dataBukuTamu->firstItem() + $index }}
							</flux:table.cell>

							<flux:table.cell class="font-medium text-zinc-900 dark:text-white">
								{{ \Carbon\Carbon::parse($tamu->tanggal_kunjungan)->translatedFormat('l, d F Y') }}
							</flux:table.cell>
							
							<flux:table.cell class="font-medium text-zinc-900 dark:text-white">
								{{ $tamu->nama_pengunjung ?? '-' }}
								<br>
								<span class="text-sm text-zinc-500">Telp. {{ $tamu->kontak_wa ?? '-' }}</span>
								<br>
								<span class="text-sm text-zinc-500">Asal: {{ $tamu->asal_instansi ?? '-' }}</span>
							</flux:table.cell>

							<flux:table.cell class="font-medium text-zinc-900 dark:text-white">
								{{ $tamu->alasan_kunjungan ?? '-' }}
								@if($tamu->tindak_lanjut)
									<br>
									<span class="text-sm text-zinc-500">Tindak Lanjut: {{ $tamu->tindak_lanjut }}</span>
								@endif
							</flux:table.cell>
							
							<flux:table.cell>
								<flux:badge size="sm" color="{{ $tamu->prioritas == 'Prioritas 1' ? 'danger' : ($tamu->prioritas == 'Prioritas 2' ? 'warning' : 'zinc') }}">
									{{ $tamu->prioritas ?? '-' }}
								</flux:badge>
							</flux:table.cell>

							<flux:table.cell>
								<flux:badge size="sm" color="{{ $tamu->status == 'Selesai' ? 'success' : ($tamu->status == 'Proses' ? 'blue' : 'zinc') }}">
									{{ $tamu->status ?? 'Tamu masuk' }}
								</flux:badge>
							</flux:table.cell>
							<flux:table.cell class="align-top text-right min-w-[120px]">
								<a href="{{ route('buku-tamu.cetak', ['nama' => urlencode($tamu->nama_pengunjung)]) }}" target="_blank">
									<flux:button size="sm" variant="ghost" icon="printer" title="Cetak Riwayat Kunjungan" class="text-zinc-600 hover:text-zinc-900" />
								</a>

								<flux:button wire:click="edit({{ $tamu->id }})" size="sm" variant="ghost" icon="pencil-square" title="Edit / Isi Tindak Lanjut" class="text-indigo-600" />
								
								@if(Auth::user()->role === 'admin')
									<flux:button wire:click="konfirmasiHapus({{ $tamu->id }})" size="sm" variant="ghost" color="danger" icon="trash" />
								@endif
							</flux:table.cell>
						</flux:table.row>
					@empty
						<flux:table.row>
							<flux:table.cell colspan="7" class="text-center text-zinc-500 py-8">
								Data tamu tidak ditemukan.
							</flux:table.cell>
						</flux:table.row>
					@endforelse
				</flux:table.rows>
			</flux:table>

			<!-- Pagination -->
			<div class="mt-4">
				{{ $dataBukuTamu->links() }}
			</div>
		</div>
	</flux:card>

	<flux:modal name="tambah-tamu" class="md:w-[700px]" wire:close="batal">
		<form wire:submit.prevent="simpan" class="space-y-5">
			<div>
				<flux:heading size="lg">Formulir Kunjungan</flux:heading>
				<flux:subheading>Isi identitas dan keperluan pengunjung (sebagai pengganti buku tamu fisik).</flux:subheading>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<flux:input wire:model="tanggal_kunjungan" type="date" label="Hari, Tanggal" required />
				<flux:input wire:model="nama_pengunjung" label="Nama Pengunjung" placeholder="Cth: Bpk. Agus" required />
				
				<flux:input wire:model="kontak_wa" label="No Kontak WA" placeholder="Cth: 081234..." />
				<flux:input wire:model="pekerjaan_status" label="Pekerjaan / Status" placeholder="Cth: Pegawai Negeri, Mahasiswa..." />
				
				<div class="md:col-span-2">
					<flux:input wire:model="asal_instansi" label="Alamat Pengunjung / Usaha / Proyek" placeholder="Cth: Universitas Udayana / Proyek Villa X" />
				</div>

				<div class="md:col-span-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
					<flux:textarea wire:model="alasan_kunjungan" label="Alasan Datang / Keterangan" rows="3" placeholder="Sampaikan secara ringkas tujuan kedatangan..." required />
				</div>
			</div>

			<!-- Dropdown Prioritas -->
			<flux:field class="mt-4">
				<flux:label>Tingkat Prioritas</flux:label>
				<flux:select wire:model="prioritas">
					<option value="Prioritas 1">Prioritas 1</option>
					<option value="Prioritas 2">Prioritas 2</option>
					<option value="Prioritas 3">Prioritas 3</option>
				</flux:select>
			</flux:field>

			<div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
				<flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
				<flux:button type="submit" variant="primary">Simpan Kunjungan</flux:button>
			</div>
		</form>
	</flux:modal>

	<flux:modal name="edit-tamu" class="md:w-[700px]" wire:close="batal">
		<form wire:submit.prevent="update" class="space-y-5">
			<div>
				<flux:heading size="lg">Edit / Isi Tindak Lanjut</flux:heading>
				<flux:subheading>Perbarui data atau tambahkan catatan tindak lanjut pasca kunjungan.</flux:subheading>
			</div>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 opacity-80">
				<flux:input wire:model="tanggal_kunjungan" type="date" label="Hari, Tanggal" required />
				<flux:input wire:model="nama_pengunjung" label="Nama Pengunjung" required />
				
				<flux:input wire:model="kontak_wa" label="No Kontak WA" />
				<flux:input wire:model="pekerjaan_status" label="Pekerjaan / Status" />
				
				<div class="md:col-span-2">
					<flux:input wire:model="asal_instansi" label="Alamat Pengunjung / Usaha / Proyek" />
				</div>
				<div class="md:col-span-2">
					<flux:textarea wire:model="alasan_kunjungan" label="Alasan Datang / Keterangan" rows="2" required />
				</div>
			</div>

			<!-- Dropdown Prioritas -->
			<flux:field class="mt-4">
				<flux:label>Tingkat Prioritas</flux:label>
				<flux:select wire:model="prioritas">
					<option value="Prioritas 1">Prioritas 1</option>
					<option value="Prioritas 2">Prioritas 2</option>
					<option value="Prioritas 3">Prioritas 3</option>
				</flux:select>
			</flux:field>

			<div class="md:col-span-2 pt-4 border-t-2 border-indigo-100 dark:border-indigo-900/30">
				<flux:textarea wire:model="tindak_lanjut" label="Tindak Lanjut" rows="3" placeholder="Catat hasil diskusi atau tindakan lanjutan yang diberikan..." class="bg-indigo-50/50 dark:bg-indigo-900/10" />
			</div>

			<!-- Dropdown Status -->
			<flux:field class="mt-4">
				<flux:label>Status Tindak Lanjut</flux:label>
				<flux:select wire:model="status">
					<option value="Tamu masuk">Tamu masuk</option>
					<option value="Proses">Proses</option>
					<option value="Selesai">Selesai</option>
				</flux:select>
			</flux:field>

			<div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
				<flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
				<flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
			</div>
		</form>
	</flux:modal>

	<flux:modal name="hapus-tamu" class="min-w-[400px]" wire:close="batal">
		<div class="mb-4">
			<flux:heading size="lg">Hapus Kunjungan?</flux:heading>
			<flux:subheading>Tindakan ini tidak dapat dibatalkan.</flux:subheading>
		</div>
		<div class="flex justify-end gap-2 mt-6">
			<flux:modal.close><flux:button variant="ghost">Batal</flux:button></flux:modal.close>
			<flux:button wire:click="destroy" variant="danger">Ya, Hapus</flux:button>
		</div>
	</flux:modal>
</div>