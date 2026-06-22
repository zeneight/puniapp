<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

new class extends Component {
	#[Layout('layouts.app')]

	#[Url(as: 'wp_id')]
    public $wajib_punia_id = '';

	// Variabel Filter Global
	public string $filterBulan = '';
	public string $filterTahun = '';

	public function mount()
	{
		// Default ke waktu sekarang
		$this->filterBulan = (string) date('n');
		$this->filterTahun = (string) date('Y');

		$this->bulan_awal = (string) date('n');
        $this->bulan_akhir = (string) date('n'); 
        $this->periode_tahun = (string) date('Y');
        $this->tanggal_bayar = date('Y-m-d');
        
        // JIKA ADA PARAMETER DARI DASHBOARD, LANGSUNG JALANKAN PENGECEKAN
        if ($this->wajib_punia_id) {
            $this->updatedWajibPuniaId($this->wajib_punia_id);
        }
	}

	public function with()
	{
		$user = Auth::user();
		$modeTahunan = empty($this->filterBulan);

		// 1. Ambil 5 Transaksi Terakhir (Sesuaikan dengan filter)
		$queryTransaksi = Transaksi::with(['wajibPunia', 'user'])
								   ->where('periode_tahun', $this->filterTahun)
								   ->orderBy('created_at', 'desc')
								   ->limit(5);

		// Jika bukan "Semua Bulan", tambahkan filter bulan
		if (!$modeTahunan) {
			$queryTransaksi->where('periode_bulan', $this->filterBulan);
		}

		if ($user->role === 'inputer') {
			$queryTransaksi->where('user_id', $user->id);
		}

		// 2. WIDGET KHUSUS ADMIN: Hitung Kinerja Petugas
		$kinerjaPetugas = collect();
		if ($user->role === 'admin') {
			// Gunakan 'use' agar variabel dari luar bisa dibaca di dalam fungsi map
			$kinerjaPetugas = User::where('role', 'inputer')->get()->map(function($petugas) use ($modeTahunan) {
				
				$q = Transaksi::where('user_id', $petugas->id)
							  ->where('periode_tahun', $this->filterTahun);
				
				if (!$modeTahunan) {
					$q->where('periode_bulan', $this->filterBulan);
				}
				
				$petugas->total_kolek = $q->sum('nominal');
				return $petugas;
				
			})->sortByDesc('total_kolek'); // Urutkan dari yang kinerjanya tertinggi
		}

		return [
			'transaksiTerbaru' => $queryTransaksi->get(),
			'kinerjaPetugas' => $kinerjaPetugas,
			'modeTahunan' => $modeTahunan // Kirim status mode ke HTML
		];
	}
};
?>

<div>
	<!-- Header & Filter Global -->
	<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
		<div>
			<flux:heading size="xl">Dashboard Sistem Punia</flux:heading>
			<flux:subheading>Ringkasan penerimaan dana dan aktivitas sistem.</flux:subheading>
		</div>

		<!-- Filter Global Dashboard -->
		<div class="flex flex-col md:flex-row w-full md:w-auto items-center gap-2">
			<flux:select wire:model.live="filterBulan" class="w-full md:w-36">
				<flux:select.option value="">Semua Bulan</flux:select.option>
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

			<flux:input wire:model.live.debounce.500ms="filterTahun" type="number" class="w-full md:w-28" />
		</div>
	</div>

	<div class="mb-8 relative">
		<div wire:loading wire:target="filterBulan, filterTahun" class="absolute inset-0 z-20 flex items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
			<div class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-zinc-800 shadow-sm rounded-full border border-zinc-200 dark:border-zinc-700">
				<svg class="w-4 h-4 animate-spin text-zinc-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
			</div>
		</div>
		<div wire:loading.class="opacity-40 pointer-events-none transition-opacity duration-200" wire:target="filterBulan, filterTahun">
			<livewire:dashboard-widget-statistik :bulan="$filterBulan" :tahun="$filterTahun" />
		</div>
	</div>

	<div class="mb-8 relative">
		<div wire:loading wire:target="filterTahun" class="absolute inset-0 z-20 flex items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
			<div class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-zinc-800 shadow-sm rounded-full border border-zinc-200 dark:border-zinc-700">
				<svg class="w-4 h-4 animate-spin text-zinc-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
			</div>
		</div>
		<div wire:loading.class="opacity-40 pointer-events-none transition-opacity duration-200" wire:target="filterTahun">
			<livewire:dashboard-rekapitulasi :tahun="$filterTahun" />
		</div>
	</div>

	<div class="mb-8 relative">
		<div wire:loading wire:target="filterBulan, filterTahun" class="absolute inset-0 z-20 flex items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
			<div class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-zinc-800 shadow-sm rounded-full border border-zinc-200 dark:border-zinc-700">
				<svg class="w-4 h-4 animate-spin text-zinc-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
			</div>
		</div>
		<div wire:loading.class="opacity-40 pointer-events-none transition-opacity duration-200" wire:target="filterBulan, filterTahun">
			<livewire:dashboard-grafik-pendapatan :bulan="$filterBulan" :tahun="$filterTahun" />
		</div>
	</div>

	<!-- 3. Grid Dua Kolom -->
	<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

		<div class="relative">
			<div wire:loading wire:target="filterBulan, filterTahun" class="absolute inset-0 z-20 flex items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
				<div class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-zinc-800 shadow-sm rounded-full border border-zinc-200 dark:border-zinc-700">
					<svg class="w-4 h-4 animate-spin text-zinc-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
				</div>
			</div>
			<div wire:loading.class="opacity-40 pointer-events-none transition-opacity duration-200" wire:target="filterBulan, filterTahun">
				<livewire:dashboard-daftar-tunggakan :bulan="$filterBulan" :tahun="$filterTahun" />
			</div>
		</div>

		<!-- Kolom Kanan: Kinerja Petugas (Admin) & Riwayat Transaksi -->
		<div class="space-y-6">
			
			@if(Auth::user()->role === 'admin')
			<flux:card class="relative">
				<div wire:loading wire:target="filterBulan, filterTahun" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
					<div class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-zinc-800 shadow-sm rounded-full border border-zinc-200 dark:border-zinc-700">
						<svg class="w-4 h-4 animate-spin text-zinc-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
						<span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">Memuat...</span>
					</div>
				</div>

				<div wire:loading.class="opacity-40 pointer-events-none transition-opacity duration-200" wire:target="filterBulan, filterTahun">
					<div class="mb-4">
                        <flux:heading size="lg">Kinerja Petugas Lapangan</flux:heading>
                        <flux:subheading>
                            {{ $modeTahunan ? 'Total punia yang dikumpulkan sepanjang tahun ' . $filterTahun . '.' : 'Total punia yang dikumpulkan pada bulan ' . $filterBulan . ' / ' . $filterTahun . '.' }}
                        </flux:subheading>
                    </div>
					<flux:table>
						<flux:table.columns>
							<flux:table.column>Nama Petugas</flux:table.column>
							<flux:table.column>Total Terkumpul</flux:table.column>
						</flux:table.columns>
						<flux:table.rows>
							@forelse ($kinerjaPetugas as $petugas)
							<flux:table.row>
								<flux:table.cell class="font-semibold">{{ $petugas->name }}</flux:table.cell>
								<flux:table.cell class="font-mono text-emerald-600 font-bold">Rp {{ number_format($petugas->total_kolek, 0, ',', '.') }}</flux:table.cell>
							</flux:table.row>
							@empty
							<flux:table.row>
								<flux:table.cell colspan="2" class="text-center text-zinc-500">Belum ada data petugas.</flux:table.cell>
							</flux:table.row>
							@endforelse
						</flux:table.rows>
					</flux:table>
				</div>
			</flux:card>
			@endif

			<flux:card class="relative">
				<div wire:loading wire:target="filterBulan, filterTahun" class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
					<div class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-zinc-800 shadow-sm rounded-full border border-zinc-200 dark:border-zinc-700">
						<svg class="w-4 h-4 animate-spin text-zinc-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
						<span class="text-xs font-medium text-zinc-700 dark:text-zinc-200">Memuat...</span>
					</div>
				</div>

				<div wire:loading.class="opacity-40 pointer-events-none transition-opacity duration-200" wire:target="filterBulan, filterTahun">
					<div class="mb-4 flex justify-between items-center">
                        <div>
                            <flux:heading size="lg">5 Transaksi Terakhir</flux:heading>
                            <flux:subheading>
                                {{ $modeTahunan ? 'Aktivitas terbaru di tahun ' . $filterTahun . '.' : 'Aktivitas terbaru di bulan ini.' }}
                            </flux:subheading>
                        </div>
                        <flux:button href="{{ route('transaksi.riwayat') }}" wire:navigate size="sm" variant="ghost">Lihat Semua</flux:button>
                    </div>
					<flux:table>
						<flux:table.columns>
							<flux:table.column>Wajib Punia</flux:table.column>
							<flux:table.column>Periode</flux:table.column>
							<flux:table.column>Petugas</flux:table.column>
							<flux:table.column>Nominal</flux:table.column>
						</flux:table.columns>
						<flux:table.rows>
							@forelse ($transaksiTerbaru as $trx)
							<flux:table.row>
								<flux:table.cell>
									<div class="font-semibold line-clamp-1">{{ $trx->wajibPunia->nama ?? 'Terhapus' }}</div>
									<div class="text-[10px] text-zinc-400">Br. {{ $trx->wajibPunia->banjar->nama_banjar ?? '-' }}</div>
								</flux:table.cell>
								<flux:table.cell class="text-xs">Bl {{ $trx->periode_bulan }}/{{ substr($trx->periode_tahun, -2) }}</flux:table.cell>
								<flux:table.cell>
									<flux:badge size="sm" variant="subtle" inset="top bottom">{{ $trx->user->name ?? '-' }}</flux:badge>
								</flux:table.cell>
								<flux:table.cell class="font-mono text-green-600 font-semibold text-sm">Rp {{ number_format($trx->nominal, 0, ',', '.') }}</flux:table.cell>
							</flux:table.row>
							@empty
							<flux:table.row>
								<flux:table.cell colspan="3" class="text-center text-zinc-500">Belum ada transaksi.</flux:table.cell>
							</flux:table.row>
							@endforelse
						</flux:table.rows>
					</flux:table>
				</div>
			</flux:card>

			

		</div>
	</div>
</div>