<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\WajibPunia;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    #[Layout('layouts.app')]

    // Variabel Form
    public string $wajib_punia_id = '';
    public string $periode_bulan = '';
    public string $periode_tahun = '2026'; // Default tahun saat ini
    public int $nominal = 0;
    public string $tanggal_bayar = '';
    public string $keterangan = '';

    public function mount()
    {
        // Set default tanggal hari ini saat halaman dibuka
        $this->tanggal_bayar = date('Y-m-d');
        // Set default bulan saat ini
        $this->periode_bulan = date('n');
    }

    // Fungsi otomatis berjalan setiap kali $wajib_punia_id berubah
    public function updatedWajibPuniaId($value)
    {
        if ($value) {
            $wp = WajibPunia::find($value);
            // Otomatis isi nominal sesuai pagu dudukan
            $this->nominal = $wp ? $wp->pagu_dudukan : 0;
        } else {
            $this->nominal = 0;
        }
    }

    protected function rules()
    {
        return [
            'wajib_punia_id' => 'required|exists:wajib_punias,id',
            'periode_bulan' => 'required|numeric|between:1,12',
            'periode_tahun' => 'required|numeric',
            'nominal' => 'required|numeric|min:0',
            'tanggal_bayar' => 'required|date',
            // Mencegah input double untuk bulan & tahun yang sama (memanfaatkan index unik di DB)
            'periode_bulan' => 'unique:transaksis,periode_bulan,NULL,id,wajib_punia_id,' . $this->wajib_punia_id . ',periode_tahun,' . $this->periode_tahun,
        ];
    }

    protected $messages = [
        'periode_bulan.unique' => 'Wajib Punia ini sudah melakukan pembayaran untuk periode bulan dan tahun tersebut.',
    ];

    public function simpan()
    {
        $this->validate();

        Transaksi::create([
            'wajib_punia_id' => $this->wajib_punia_id,
            'user_id' => Auth::id(), // ID petugas yang sedang login
            'periode_bulan' => $this->periode_bulan,
            'periode_tahun' => $this->periode_tahun,
            'nominal' => $this->nominal,
            'tanggal_bayar' => $this->tanggal_bayar,
            'keterangan' => $this->keterangan,
        ]);

        // Reset form setelah sukses, kecuali bulan dan tahun
        $this->reset(['wajib_punia_id', 'nominal', 'keterangan']);
        
        // Memunculkan pesan toast sukses (harus ada komponen toast di layout utama nanti)
        // $this->js('$flux.toast("Pembayaran berhasil dicatat!")');
    }

    public function with()
    {
        // query dasar
        $queryWajibPunia = WajibPunia::where('is_active', true);

        // Cek role user yang sedang login
        if (Auth::user()->role === 'inputer') {
            // Jika inputer, KUNCI hanya untuk Wajib Punia miliknya saja
            $queryWajibPunia->where('user_id', Auth::id());
        }

        return [
            // 3. Eksekusi query
            'wajibPunias' => $queryWajibPunia->orderBy('nama')->get(),
            
            'riwayatHariIni' => Transaksi::with('wajib_punia')
                                ->where('user_id', Auth::id())
                                ->whereDate('created_at', date('Y-m-d'))
                                ->latest()
                                ->get()
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
                        @foreach($wajibPunias as $wp)
                            <flux:select.option value="{{ $wp->id }}">
                                {{ $wp->nama }} (Br. {{ $wp->banjar->nama_banjar }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:select wire:model="periode_bulan" label="Periode Bulan">
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

                        <flux:input wire:model="periode_tahun" type="number" label="Periode Tahun" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input wire:model="nominal" type="number" label="Nominal Pembayaran (Rp)" />
                        <flux:input wire:model="tanggal_bayar" type="date" label="Tanggal Transaksi" />
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