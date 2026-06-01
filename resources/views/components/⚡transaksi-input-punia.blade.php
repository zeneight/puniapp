<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Transaksi;
use App\Models\WajibPunia;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    #[Layout('layouts.app')]

    public $wajib_punia_id = '';
    public $bulan_awal = '';   // Menggantikan periode_bulan
    public $bulan_akhir = '';  // Tambahan baru
    public $periode_tahun = '';
    public $tanggal_bayar = '';
    public $nominal = '';
    public $keterangan = '';

    public function mount()
    {
        $this->bulan_awal = (string) date('n');
        $this->bulan_akhir = (string) date('n'); // Default sama dengan bulan awal
        $this->periode_tahun = (string) date('Y');
        $this->tanggal_bayar = date('Y-m-d');
    }

    // Fungsi otomatis mengisi nominal saat Wajib Punia dipilih
    public function updatedWajibPuniaId($value)
    {
        if ($value) {
            $wp = WajibPunia::find($value);
            if ($wp) {
                $this->nominal = $wp->pagu_dudukan;
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
                    
                    <flux:select wire:model.live="wajib_punia_id" label="Pilih Wajib Punia" placeholder="Cari Nama Usaha / Donatur..." searchable>
                        @foreach($daftarWajibPunia as $wp)
                            <flux:select.option value="{{ $wp->id }}">
                                {{ $wp->nama }} (Br. {{ $wp->banjar->nama_banjar }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>

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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input wire:model="nominal" type="number" label="Nominal Per Bulan (Rp)" description="Terisi otomatis sesuai pagu. Ubah jika ada kurang/lebih bayar." />
                        
                    </div>

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