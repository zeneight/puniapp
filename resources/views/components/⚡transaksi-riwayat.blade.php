<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;
    
    #[Layout('layouts.app')]
    
    public string $search = '';
    public string $sortBy = 'tanggal_bayar'; // Default urut berdasarkan tanggal
    public string $sortDir = 'desc'; // Default dari yang paling baru
    
    public string $filterBulan = '';
    public string $filterTahun = '';

    public function mount()
    {
        // Set default filter ke bulan & tahun saat ini
        $this->filterBulan = (string) date('n');
        $this->filterTahun = (string) date('Y');
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterBulan() { $this->resetPage(); }
    public function updatingFilterTahun() { $this->resetPage(); }

    public function setSortBy($kolom)
    {
        if ($this->sortBy === $kolom) {
            $this->sortDir = ($this->sortDir === 'asc') ? 'desc' : 'asc';
            return;
        }
        $this->sortBy = $kolom;
        $this->sortDir = 'asc';
    }

    public function resetFilter()
    {
        $this->reset(['search', 'sortBy', 'sortDir']);
        $this->filterBulan = (string) date('n');
        $this->filterTahun = (string) date('Y');
        $this->sortBy = 'tanggal_bayar';
        $this->sortDir = 'desc';
        $this->resetPage();
    }

    public function with()
    {
        $query = Transaksi::with(['wajib_punia', 'user'])
            // Kunci data jika yang login adalah inputer
            ->when(Auth::user()->role === 'inputer', function ($q) {
                $q->where('user_id', Auth::id());
            })
            // Filter Search (Mencari nama Wajib Punia)
            ->when($this->search, function ($q) {
                $q->whereHas('wajib_punia', function ($qWP) {
                    $qWP->where('nama', 'like', '%' . $this->search . '%');
                });
            })
            // Filter Bulan & Tahun
            ->when($this->filterBulan, function ($q) {
                $q->where('periode_bulan', $this->filterBulan);
            })
            ->when($this->filterTahun, function ($q) {
                $q->where('periode_tahun', $this->filterTahun);
            })
            ->orderBy($this->sortBy, $this->sortDir);

        return [
            'transaksis' => $query->paginate(10),
            'totalNominal' => (clone $query)->sum('nominal'), // Hitung total dari data yang difilter
        ];
    }
};
?>

<div>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <div>
            <flux:heading size="xl">Data Riwayat Transaksi</flux:heading>
            <flux:subheading>Pantau dan kelola seluruh catatan penerimaan punia.</flux:subheading>
        </div>
        
        <div class="flex flex-col md:flex-row w-full md:w-auto items-center gap-2">
            
            <flux:select wire:model.live="filterBulan" class="w-full md:w-36" aria-label="Filter Bulan">
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

            <flux:input wire:model.live.debounce.500ms="filterTahun" type="number" placeholder="Tahun..." class="w-full md:w-28" />

            <flux:input wire:model.live.debounce.300ms="search" type="search" icon="magnifying-glass" placeholder="Cari nama tempat/donatur..." class="w-full md:w-64" />
            
            @if($search !== '' || $filterBulan !== (string) date('n') || $filterTahun !== (string) date('Y'))
                <flux:button wire:click="resetFilter" variant="danger" icon="x-mark" class="px-3" tooltip="Reset Filter">
                    Reset
                </flux:button>
            @endif
        </div>
    </div>

    <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg flex items-center justify-between">
        <div class="text-sm text-emerald-800 dark:text-emerald-300 font-medium">Total Nominal dari data yang ditampilkan:</div>
        <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($totalNominal, 0, ',', '.') }}</div>
    </div>

    <flux:card class="relative">
        
        <div wire:loading class="absolute inset-0 z-10 flex items-center justify-center bg-white/50 dark:bg-zinc-900/50 backdrop-blur-sm rounded-xl">
            <div class="flex items-center gap-3 px-5 py-2.5 bg-white dark:bg-zinc-800 shadow-lg rounded-full border border-zinc-200 dark:border-zinc-700">
                <svg class="w-5 h-5 animate-spin text-zinc-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Menyaring data...</span>
            </div>
        </div>

        <div wire:loading.class="opacity-40 pointer-events-none transition-opacity duration-200">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'tanggal_bayar'" :direction="$sortDir" wire:click="setSortBy('tanggal_bayar')">Tanggal Masuk</flux:table.column>
                    <flux:table.column>Wajib Punia</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'periode_bulan'" :direction="$sortDir" wire:click="setSortBy('periode_bulan')">Untuk Periode</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'nominal'" :direction="$sortDir" wire:click="setSortBy('nominal')">Nominal</flux:table.column>
                    
                    @if(Auth::user()->role === 'admin')
                        <flux:table.column>Petugas</flux:table.column>
                    @endif
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($transaksis as $trx)
                    <flux:table.row>
                        <flux:table.cell>{{ \Carbon\Carbon::parse($trx->tanggal_bayar)->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="font-semibold text-zinc-800 dark:text-white">{{ $trx->wajib_punia->nama ?? 'Terhapus' }}</div>
                            <div class="text-xs text-zinc-500 line-clamp-1">{{ $trx->keterangan ?? '-' }}</div>
                        </flux:table.cell>
                        <flux:table.cell>Bulan {{ $trx->periode_bulan }} / {{ $trx->periode_tahun }}</flux:table.cell>
                        <flux:table.cell class="font-mono font-semibold text-green-600">Rp {{ number_format($trx->nominal, 0, ',', '.') }}</flux:table.cell>
                        
                        @if(Auth::user()->role === 'admin')
                            <flux:table.cell>
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $trx->user->name ?? '-' }}</span>
                            </flux:table.cell>
                        @endif
                    </flux:table.row>
                    @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-500 py-6">
                            Tidak ada data transaksi yang sesuai dengan filter.
                        </flux:table.cell>
                    </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            
            <div class="mt-4">
                {{ $transaksis->links() }}
            </div>
        </div>
    </flux:card>
</div>