<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Transaksi;
use App\Models\WajibPunia;
use Illuminate\Support\Facades\Auth;

new class extends Component {
	#[Layout('layouts.app')]

	public $wajib_punia_id = '';
	public $bulan_awal = ''; 
	public $bulan_akhir = '';  
	public $periode_tahun = '';
	public $tanggal_bayar = '';
	public $nominal = '';
	public $keterangan = '';

	public array $infoTunggakan = [];

	public function mount()
	{
		$this->bulan_awal = (string) date('n');
		$this->bulan_akhir = (string) date('n'); // Default sama dengan bulan awal
		$this->periode_tahun = (string) date('Y');
		$this->tanggal_bayar = date('Y-m-d');
	}

	// Fungsi otomatis mengisi nominal saat Wajib Punia dipilih dan fungsi tunggakan
	public function updatedWajibPuniaId($value)
	{
		$this->infoTunggakan = []; // Reset info setiap kali ganti orang
		
		if ($value) {
			$wp = WajibPunia::find($value);
			if ($wp) {
				// 1. Isi nominal otomatis
				$this->nominal = $wp->pagu_dudukan;
				
				// 2. Cek tunggakan untuk tahun berjalan
				$tahunIni = (int) $this->periode_tahun;
				$bulanSekarang = (int) date('n'); // Bulan 6 (Juni 2026)

				// Cari bulan apa saja yang sudah dibayar di tahun ini
				$bulanTerbayar = Transaksi::where('wajib_punia_id', $value)
										  ->where('periode_tahun', $tahunIni)
										  ->pluck('periode_bulan')
										  ->toArray();

				// Deteksi tunggakan: Bandingkan dari bulan 1 sampai bulan sekarang
				$menunggak = [];
				for ($i = 1; $i <= $bulanSekarang; $i++) {
					if (!in_array($i, $bulanTerbayar)) {
						$menunggak[] = $i;
					}
				}

				// Jika ada tunggakan, simpan ke state array
				if (count($menunggak) > 0) {
					$this->infoTunggakan = $menunggak;
					
					// (Opsional) Auto-set bulan_awal ke bulan tunggakan pertama
					$this->bulan_awal = (string) min($menunggak);
				} else {
					// Jika lunas semua, arahkan ke bulan depan
					$this->bulan_awal = (string) ($bulanSekarang + 1);
				}
			}
		} else {
			$this->nominal = '';
		}
	}

	public function simpan()
	{
		$this->validate([
			'wajib_punia_id' => 'required',
			'bulan_awal' => 'required|numeric|min:1|max:12',
			'bulan_akhir' => 'required|numeric|min:1|max:12|gte:bulan_awal', // Harus lebih besar/sama dengan bulan awal
			'periode_tahun' => 'required|numeric',
			'tanggal_bayar' => 'required|date',
			'nominal' => 'required|numeric|min:1',
		], [
			'bulan_akhir.gte' => 'Bulan akhir tidak boleh lebih kecil dari bulan awal.'
		]);

		$jumlahBulan = 0;
		$bulanDilewati = 0;

		// Looping berdasarkan rentang bulan
		for ($bln = $this->bulan_awal; $bln <= $this->bulan_akhir; $bln++) {
			
			// Pengecekan ekstra: Cegah input ganda untuk bulan yang sama
			$sudahBayar = Transaksi::where('wajib_punia_id', $this->wajib_punia_id)
								   ->where('periode_bulan', $bln)
								   ->where('periode_tahun', $this->periode_tahun)
								   ->exists();

			if (!$sudahBayar) {
				Transaksi::create([
					'wajib_punia_id' => $this->wajib_punia_id,
					'user_id' => Auth::id(),
					'periode_bulan' => $bln,
					'periode_tahun' => $this->periode_tahun,
					'tanggal_bayar' => $this->tanggal_bayar,
					'nominal' => $this->nominal, // Dicatat sebagai nominal PER BULAN
					'keterangan' => $this->keterangan,
				]);
				$jumlahBulan++;
			} else {
				$bulanDilewati++;
			}
		}

		// Tampilkan notifikasi yang dinamis
		if ($jumlahBulan > 0) {
			$pesan = "Berhasil menyimpan pembayaran untuk $jumlahBulan bulan.";
			if ($bulanDilewati > 0) {
				$pesan .= " ($bulanDilewati bulan dilewati karena sudah lunas sebelumnya).";
			}
			\Flux::toast($pesan, variant: 'success');
		} elseif ($bulanDilewati > 0) {
			\Flux::toast('Gagal! Semua bulan dalam rentang tersebut sudah lunas sebelumnya.', variant: 'danger');
		}

		$this->reset(['wajib_punia_id', 'nominal', 'keterangan']);
		$this->mount(); // Kembalikan form ke kondisi default
	}

	public function with()
	{
		$queryWP = WajibPunia::where('is_active', true);
		if (Auth::user()->role === 'inputer') {
			$queryWP->where('user_id', Auth::id());
		}

		$queryRiwayat = Transaksi::with(['wajib_punia', 'user'])
								 ->whereDate('tanggal_bayar', date('Y-m-d'))
								 ->orderBy('created_at', 'desc');
		if (Auth::user()->role === 'inputer') {
			$queryRiwayat->where('user_id', Auth::id());
		}

		return [
			'daftarWajibPunia' => $queryWP->orderBy('nama')->get(),
			'riwayatHariIni' => $queryRiwayat->get(),
		];
	}
};
?>

<div>
	<div class="mb-6">
		<flux:heading size="xl">Input Pembayaran Punia</flux:heading>
		<flux:subheading>Catat transaksi penerimaan punia dudukan bulanan.</flux:subheading>
	</div>
	<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
		
		<div class="col-span-2">
			<flux:card>
				<form wire:submit="simpan" class="space-y-6">
					
					<flux:select wire:model.live="wajib_punia_id" label="Wajib Punia" placeholder="Pilih Wajib Punia...">
						@foreach ($daftarWajibPunia as $wp)
							<flux:select.option value="{{ $wp->id }}">{{ $wp->nama }} (Br. {{ $wp->banjar->nama_banjar ?? '-' }})</flux:select.option>
						@endforeach
					</flux:select>

					<!-- Indikator Loading Khusus Dropdown Wajib Punia -->
					<div wire:loading wire:target="wajib_punia_id" class="mt-2 flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400 font-medium animate-pulse">
						<svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
							<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
							<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
						</svg>
						Mengecek riwayat pembayaran...
					</div>

					<!-- Bungkus Alert Tunggakan dengan wire:loading.remove -->
					<!-- (Ini akan menyembunyikan alert lama saat sistem sedang loading data baru) -->
					<div wire:loading.remove wire:target="wajib_punia_id">
						@if(count($infoTunggakan) > 0)
							<div class="mt-2 p-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-md flex gap-3 items-start animate-pulse-once">
								<flux:icon.exclamation-triangle class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" />
								<div>
									<div class="text-sm font-bold text-amber-800 dark:text-amber-300">
										Terdeteksi {{ count($infoTunggakan) }} Bulan Tunggakan (Tahun {{ $periode_tahun }})
									</div>
									<div class="text-xs text-amber-700 dark:text-amber-400 mt-1">
										Belum lunas pada bulan ke: <strong>{{ implode(', ', $infoTunggakan) }}</strong>.<br>
										Bulan awal telah disesuaikan otomatis.
									</div>
								</div>
							</div>
						@endif
					</div>

					<!-- Baris Rentang Bulan -->
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<flux:select wire:model="bulan_awal" label="Mulai Bulan">
							<flux:select.option value="1">Januari</flux:select.option>
							<flux:select.option value="2">Februari</flux:select.option>
							<flux:select.option value="3">Maret</flux:select.option>
							<flux:select.option value="4">April</flux:select.option>
							<flux:select.option value="5">Mei</flux:select.option>
							<flux:select.option value="6">Juni</flux:select.option>
							<flux:select.option value="7">Juli</flux:select.option>
							<flux:select.option value="8">Agustus</flux:select.option>
							<flux:select.option value="9">September</flux:select.option>
							<flux:select.option value="10">Oktober</flux:select.option>
							<flux:select.option value="11">November</flux:select.option>
							<flux:select.option value="12">Desember</flux:select.option>
						</flux:select>

						<flux:select wire:model="bulan_akhir" label="Sampai Bulan (Rapel)">
							<flux:select.option value="1">Januari</flux:select.option>
							<flux:select.option value="2">Februari</flux:select.option>
							<flux:select.option value="3">Maret</flux:select.option>
							<flux:select.option value="4">April</flux:select.option>
							<flux:select.option value="5">Mei</flux:select.option>
							<flux:select.option value="6">Juni</flux:select.option>
							<flux:select.option value="7">Juli</flux:select.option>
							<flux:select.option value="8">Agustus</flux:select.option>
							<flux:select.option value="9">September</flux:select.option>
							<flux:select.option value="10">Oktober</flux:select.option>
							<flux:select.option value="11">November</flux:select.option>
							<flux:select.option value="12">Desember</flux:select.option>
						</flux:select>
					</div>

					<!-- Bagian Tahun dan Nominal -->
					<div x-data="{
						raw: @entangle('nominal'),
						formatted: '',
						
						init() {
							if (this.raw) {
								this.formatted = new Intl.NumberFormat('id-ID').format(this.raw);
							}

							$watch('raw', value => {
								this.formatted = value ? new Intl.NumberFormat('id-ID').format(value) : '';
							});
						},
						
						formatInput(value) {
							let angkaBersih = value.replace(/[^0-9]/g, '');
							this.raw = angkaBersih ? parseInt(angkaBersih) : null;
							this.formatted = angkaBersih ? new Intl.NumberFormat('id-ID').format(angkaBersih) : '';
						}
					}">

						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<flux:field>
								<flux:label>Nominal Per Bulan</flux:label>
								<div class="text-[11px] text-zinc-500 mt-1">
									Terisi otomatis sesuai pagu. Ubah jika ada kurang/lebih bayar.
								</div>
								
								<flux:input.group>
									<flux:input.group.prefix>Rp</flux:input.group.prefix>
									
									<flux:input 
										x-model="formatted" 
										@input="formatInput($event.target.value)" 
										placeholder="Contoh: 150.000"
									/>
								</flux:input.group>
								
							</flux:field>
						</div>
					</div>
					<!-- x-data nominal pagu -->

					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<flux:input wire:model="periode_tahun" type="number" label="Tahun" />
					</div>

					<flux:textarea wire:model="keterangan" label="Catatan Tambahan (Opsional)" placeholder="Misal: Pembayaran rapel, titipan, dsb." />

					<div class="flex justify-end pt-2">
						<flux:button type="submit" variant="primary" icon="check-circle" class="w-full sm:w-auto">Simpan Pembayaran</flux:button>
					</div>
				</form>
			</flux:card>
		</div>

		<div class="col-span-1">
			<flux:card>
				<div class="mb-4 pb-2 border-b">
					<flux:heading size="lg">Riwayat Input Anda Hari Ini</flux:heading>
				</div>
				
				<div class="space-y-4">
					@forelse($riwayatHariIni as $trx)
						<div class="flex justify-between items-center bg-zinc-50 dark:bg-zinc-800 p-3 rounded-lg">
							<div>
								<div class="font-semibold text-sm">{{ $trx->wajib_punia->nama }}</div>
								<div class="text-xs text-zinc-500">Bulan {{ $trx->periode_bulan }} - {{ $trx->periode_tahun }}</div>
							</div>
							<div class="text-right">
								<div class="font-bold text-green-600">Rp {{ number_format($trx->nominal, 0, ',', '.') }}</div>
							</div>
						</div>
					@empty
						<div class="text-center text-sm text-zinc-500 py-4">Belum ada transaksi diinput hari ini.</div>
					@endforelse
				</div>

				<flux:button class="mt-4" href="{{ route('transaksi.riwayat') }}" wire:navigate variant="subtle" icon="clock">
					Lihat Semua Riwayat
				</flux:button>
			</flux:card>
		</div>

	</div>
</div>