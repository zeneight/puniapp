<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Transaksi;
use App\Models\WajibPunia;
use App\Models\Kategori;
use Illuminate\Support\Facades\Auth;

use Livewire\Attributes\Url;

use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component {
	use WithFileUploads;

    #[Url(as: 'wp_id')]

	#[Layout('layouts.app')]

	// Variabel Form Input
	public $wajib_punia_id = '';
	public $kategori_id = '';
	public $bulan_awal = ''; 
	public $bulan_akhir = '';  
	public $periode_tahun = '';
	public $tanggal_bayar = '';
	public $nominal = '';
	public $keterangan = '';
	public $bukti_dokumen;

	// Variabel Modal Edit
    public $edit_transaksi_id;
    public $edit_nominal;
    public $edit_keterangan;
    public $edit_bukti_lama;
    public $edit_bukti_baru;

	public $edit_nama_wp;
    public $edit_periode;
    public $edit_tanggal_bayar;
	public $edit_jenis_pembayaran;

	public array $infoTunggakan = [];
	public bool $isLunas = false;

	public function mount()
	{
        if ($this->wajib_punia_id) {
            $this->tarikDataWajibPunia($this->wajib_punia_id);
        }

		$this->bulan_awal = (string) date('n');
		$this->bulan_akhir = (string) date('n'); // Default sama dengan bulan awal
		$this->periode_tahun = (string) date('Y');
		$this->tanggal_bayar = date('Y-m-d');
	}

	// Fungsi otomatis mengisi nominal saat Wajib Punia dipilih dan fungsi tunggakan
	public function updatedWajibPuniaId($value)
	{
		$this->tarikDataWajibPunia($value);
	}

    // Fungsi khusus untuk mengisi form Kategori dan Nominal
    private function tarikDataWajibPunia($value)
    {
        if (!$value) {
            $this->reset(['kategori_id', 'nominal']); // Kosongkan jika tidak ada ID
            return;
        }

        $wp = WajibPunia::find($value);
        
        if ($wp) {
            $this->wajib_punia_id = $wp->id;
            $this->kategori_id = $wp->kategori_id; // Otomatis pilih kategori di dropdown
            $this->nominal = $wp->pagu_dudukan;    // Otomatis isi nominal sesuai pagu
            
            // Tambahkan variabel lain di sini jika ada yang perlu diisi otomatis
            $this->infoTunggakan = []; // Reset info setiap kali ganti orang
            $this->kategori_id = '';
            
            if ($value) {
                $wp = WajibPunia::find($value);
                if ($wp) {
                    // 1. Isi nominal otomatis
                    $this->nominal = $wp->pagu_dudukan;
                    $this->kategori_id = $wp->kategori_id;
                    $this->cekTunggakan();
                    
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
    }

	// Fungsi trigger saat Tahun Periode diubah
    public function updatedPeriodeTahun()
    {
        if ($this->wajib_punia_id) {
            $this->cekTunggakan();
        }
    }

    // Fungsi khusus untuk mengecek tunggakan & status lunas
    public function cekTunggakan()
    {
        $this->infoTunggakan = [];
        $this->isLunas = false;

        if (!$this->wajib_punia_id || !$this->periode_tahun) return;

        $tahunIni = (int) $this->periode_tahun;
        $tahunSekarang = (int) date('Y');
        
        // Jika cek tahun lalu, batasnya bulan 12. Jika tahun ini, batasnya bulan berjalan.
        $bulanBatas = ($tahunIni === $tahunSekarang) ? (int) date('n') : (($tahunIni < $tahunSekarang) ? 12 : 0);

        $bulanTerbayar = Transaksi::where('wajib_punia_id', $this->wajib_punia_id)
                                  ->where('periode_tahun', $tahunIni)
                                  ->pluck('periode_bulan')
                                  ->toArray();

        $menunggak = [];
        for ($i = 1; $i <= $bulanBatas; $i++) {
            if (!in_array($i, $bulanTerbayar)) {
                $menunggak[] = $i;
            }
        }

        if (count($menunggak) > 0) {
            $this->infoTunggakan = $menunggak;
            $this->bulan_awal = (string) min($menunggak);
            $this->bulan_akhir = (string) min($menunggak);
        } else {
            // Jika tidak ada tunggakan, tandai LUNAS
            $this->isLunas = true;
            
            // Arahkan otomatis ke bulan pertama yang belum dibayar di tahun tersebut
            $bulanTerakhirDibayar = max($bulanTerbayar ?: [0]);
            if ($bulanTerakhirDibayar < 12) {
                $this->bulan_awal = (string) ($bulanTerakhirDibayar + 1);
                $this->bulan_akhir = (string) ($bulanTerakhirDibayar + 1);
            }
        }
    }

	public function simpan()
	{
		$this->validate([
			'wajib_punia_id' => 'required',
			'kategori_id' => 'required',
			'bulan_awal' => 'required|numeric|min:1|max:12',
			'bulan_akhir' => 'required|numeric|min:1|max:12|gte:bulan_awal', // Harus lebih besar/sama dengan bulan awal
			'periode_tahun' => 'required|numeric',
			'tanggal_bayar' => 'required|date',
			'nominal' => 'required|numeric|min:1',
			'tanggal_bayar' => 'required|date'
		], [
			'bulan_akhir.gte' => 'Bulan akhir tidak boleh lebih kecil dari bulan awal.'
		]);

		// proses upload file
		$pathBukti = null;
        if ($this->bukti_dokumen) {
            $pathBukti = $this->bukti_dokumen->store('bukti_transaksi', 'public');
        }

		$jumlahBulan = 0;
		$bulanDilewati = 0;

		// Looping berdasarkan rentang bulan
		for ($bln = $this->bulan_awal; $bln <= $this->bulan_akhir; $bln++) {
			
			// Pengecekan ekstra: Cegah input ganda untuk bulan yang sama
			$sudahBayar = Transaksi::where('wajib_punia_id', $this->wajib_punia_id)
								   ->where('periode_bulan', $bln)
								   ->where('periode_tahun', $this->periode_tahun)
								   ->where('jenis_pembayaran_id', $this->kategori_id)
								   ->exists();

			if (!$sudahBayar) {
				Transaksi::create([
					'wajib_punia_id' => $this->wajib_punia_id,
					'user_id' => Auth::id(),
					'periode_bulan' => $bln,
					'periode_tahun' => $this->periode_tahun,
					'tanggal_bayar' => $this->tanggal_bayar,
					'jenis_pembayaran_id' => $this->kategori_id,
					'nominal' => $this->nominal, // Dicatat sebagai nominal PER BULAN
					'keterangan' => $this->keterangan,
					'bukti_dokumen' => $pathBukti,
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

		$this->reset(['wajib_punia_id', 'nominal', 'keterangan', 'bukti_dokumen']);
		$this->mount(); // Kembalikan form ke kondisi default
	}

	public function editTransaksi($id)
    {
        // 
        $trx = Transaksi::with(['wajibPunia', 'jenisPembayaran'])->findOrFail($id);
        
        $this->edit_transaksi_id = $trx->id;
        $this->edit_nominal = $trx->nominal;
        $this->edit_keterangan = $trx->keterangan;
        $this->edit_bukti_lama = $trx->bukti_dokumen;

		// Isi variabel info read-only
        $this->edit_nama_wp = $trx->wajibPunia->nama ?? 'Data Terhapus';
        $this->edit_periode = 'Bulan ' . $trx->periode_bulan . ' - ' . $trx->periode_tahun;
        $this->edit_tanggal_bayar = $trx->tanggal_bayar;
		$this->edit_jenis_pembayaran = $trx->jenisPembayaran->nama_kategori ?? 'Umum';
        
        $this->resetValidation();
        $this->js('$flux.modal("edit-transaksi").show()');
    }

	public function updateTransaksi()
    {
        $this->validate([
            'edit_nominal' => 'required|numeric|min:1',
            'edit_keterangan' => 'nullable|string',
            'edit_bukti_baru' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $trx = Transaksi::findOrFail($this->edit_transaksi_id);
        
        // Jika admin mengupload bukti yang baru
        if ($this->edit_bukti_baru) {
            // Hapus file fisik yang lama (agar server tidak penuh)
            if ($trx->bukti_dokumen && Storage::disk('public')->exists($trx->bukti_dokumen)) {
                Storage::disk('public')->delete($trx->bukti_dokumen);
            }
            // Simpan yang baru
            $trx->bukti_dokumen = $this->edit_bukti_baru->store('bukti_transaksi', 'public');
        }

        $trx->nominal = $this->edit_nominal;
        $trx->keterangan = $this->edit_keterangan;
        $trx->save();

        $this->reset(['edit_transaksi_id', 'edit_nominal', 'edit_keterangan', 'edit_bukti_lama', 'edit_bukti_baru', 'edit_jenis_pembayaran', 'edit_nama_wp', 'edit_periode', 'edit_tanggal_bayar']);
        $this->js('$flux.modal("edit-transaksi").close()');
        \Flux::toast('Data transaksi berhasil diperbarui!', variant: 'success');
    }

	public function with()
	{
		$queryWP = WajibPunia::where('is_active', true);
		if (Auth::user()->role === 'inputer') {
			$queryWP->where('user_id', Auth::id());
		}

		$queryRiwayat = Transaksi::with(['wajibPunia', 'user', 'jenisPembayaran']) 
                                 ->whereDate('tanggal_bayar', date('Y-m-d'))
                                 ->orderBy('created_at', 'desc')
                                 ->limit(15);
								 
		if (Auth::user()->role === 'inputer') {
			$queryRiwayat->where('user_id', Auth::id());
		}

		return [
			'daftarWajibPunia' => $queryWP->orderBy('nama')->get(),
			'daftarKategori' => Kategori::orderBy('nama_kategori')->get(),
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
                <form wire:submit="simpan" class="space-y-8">
                    
                    <div>
                        <div class="mb-4 pb-2 border-b border-zinc-200 dark:border-zinc-700">
                            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">1. Data Wajib Punia</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label>Tanggal Pembayaran Transaksi</flux:label>
                                <flux:input type="date" wire:model="tanggal_bayar" required />
                            </flux:field>

                            <div class="hidden md:block"></div>

                            <flux:select wire:model.live="wajib_punia_id" label="Wajib Punia" placeholder="Pilih Wajib Punia..." searchable description="Pilih nama wajib punia yang akan dibayarkan.">
                                @foreach ($daftarWajibPunia as $wp)
                                    <flux:select.option value="{{ $wp->id }}">{{ $wp->nama }}</flux:select.option>
                                @endforeach
                            </flux:select>

                            <flux:select wire:model="kategori_id" label="Kategori Punia" placeholder="Pilih Kategori..." description="Otomatis terisi sesuai WP.">
                                @foreach ($daftarKategori as $kat)
                                    <flux:select.option value="{{ $kat->id }}">{{ $kat->nama_kategori }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    </div>

                    <div>
                        <div class="mb-4 pb-2 border-b border-zinc-200 dark:border-zinc-700">
                            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">2. Periode Tagihan</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <flux:input wire:model.live.debounce.500ms="periode_tahun" type="number" label="Tahun Periode" />
                            
                            <flux:select wire:model="bulan_awal" label="Mulai Bulan">
                                @for($i=1; $i<=12; $i++)
                                    <flux:select.option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</flux:select.option>
                                @endfor
                            </flux:select>

                            <flux:select wire:model="bulan_akhir" label="Sampai Bulan (Rapel)">
                                @for($i=1; $i<=12; $i++)
                                    <flux:select.option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</flux:select.option>
                                @endfor
                            </flux:select>
                        </div>

                        <div wire:loading wire:target="wajib_punia_id, periode_tahun" class="mb-2 flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400 font-medium animate-pulse">
                            <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Mengecek riwayat pembayaran...
                        </div>

                        <div wire:loading.remove wire:target="wajib_punia_id, periode_tahun">
                            @if(count($infoTunggakan) > 0)
                                <div class="p-3 bg-amber-50 border border-amber-200 rounded-md flex gap-3 items-start animate-pulse-once">
                                    <flux:icon.exclamation-triangle class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" />
                                    <div>
                                        <div class="text-sm font-bold text-amber-800">
                                            Terdeteksi {{ count($infoTunggakan) }} Bulan Tunggakan (Tahun {{ $periode_tahun }})
                                        </div>
                                        <div class="text-xs text-amber-700 mt-1">
                                            Belum lunas pada bulan ke: <strong>{{ implode(', ', $infoTunggakan) }}</strong>.<br>
                                            Pilihan "Mulai Bulan" telah disesuaikan otomatis.
                                        </div>
                                    </div>
                                </div>
                            @elseif($isLunas)
                                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-md flex gap-3 items-start">
                                    <flux:icon.check-circle class="w-5 h-5 text-emerald-500 mt-0.5 shrink-0" />
                                    <div>
                                        <div class="text-sm font-bold text-emerald-800">
                                            Wajib Punia Telah Lunas (Tahun {{ $periode_tahun }})
                                        </div>
                                        <div class="text-xs text-emerald-700 mt-1">
                                            Semua tagihan hingga batas bulan di tahun ini sudah terbayar. Anda bisa menginput untuk pembayaran ke depan (Mata Uang/Titipan).
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="mb-4 pb-2 border-b border-zinc-200 dark:border-zinc-700">
                            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">3. Rincian & Bukti Pembayaran</h3>
                        </div>

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
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <flux:field>
                                    <flux:label>Nominal Per Bulan</flux:label>
                                    <div class="text-[11px] text-zinc-500 mt-1 mb-2">Terisi otomatis sesuai pagu. Ubah jika ada penyesuaian.</div>
                                    <flux:input.group>
                                        <flux:input.group.prefix>Rp</flux:input.group.prefix>
                                        <flux:input x-model="formatted" @input="formatInput($event.target.value)" required placeholder="Contoh: 150.000" />
                                    </flux:input.group>
                                </flux:field>

                                <flux:field>
                                    <flux:label>Bukti Transfer / Kuitansi (Opsional)</flux:label>
                                    <div class="text-[11px] text-zinc-500 mt-1 mb-2">Format: JPG, PNG, PDF. Maksimal 2MB.</div>
                                    <flux:input type="file" wire:model="bukti_dokumen" accept="image/*,.pdf" class="w-full" />
                                    <div wire:loading wire:target="bukti_dokumen" class="text-xs text-indigo-600 mt-1">Mengunggah file...</div>
                                    <flux:error name="bukti_dokumen" />
                                </flux:field>
                            </div>
                        </div>

                        <flux:textarea wire:model="keterangan" label="Catatan Tambahan (Opsional)" placeholder="Misal: Pembayaran rapel, titipan, dsb." rows="2" />
                    </div>

                    <div class="flex justify-end pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <flux:button type="submit" variant="primary" icon="check-circle" class="w-full sm:w-auto">Simpan Pembayaran</flux:button>
                    </div>
                </form>
            </flux:card>
        </div>

        <div class="col-span-1">
            <flux:card>
                <div class="mb-4 pb-2 border-b">
                    <flux:heading size="lg">Riwayat Input Anda</flux:heading>
                    <div class="text-xs text-zinc-500 mt-1">10 Transaksi terakhir</div>
                </div>
                
                <div class="space-y-3 max-h-[450px] overflow-y-auto pr-2 custom-scrollbar">
                    @forelse($riwayatHariIni as $trx)
                        <div class="flex justify-between items-center bg-zinc-50 dark:bg-zinc-800 p-3 rounded-lg border border-zinc-100 dark:border-zinc-700">
                            <div>
                                <div class="font-semibold text-sm">{{ $trx->wajibPunia->nama ?? 'Data Terhapus' }}</div>
                                
                                <div class="text-[11px] text-zinc-600 dark:text-zinc-400 font-medium flex items-center gap-1.5 mt-0.5">
                                    <span class="bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 px-1.5 py-0.5 rounded text-[10px] font-bold">
                                        {{ $trx->jenisPembayaran->nama_kategori ?? 'Umum' }}
                                    </span>
                                    
                                    <span>• Bln {{ $trx->periode_bulan }} - {{ $trx->periode_tahun }}</span>
                                    
                                    @if($trx->bukti_transfer)
                                        <flux:icon.paper-clip class="w-3 h-3 text-zinc-400" title="Ada Lampiran Bukti" />
                                    @endif
                                </div>

                                <div class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1">
                                    Tgl Bayar: {{ \Carbon\Carbon::parse($trx->tanggal_bayar)->format('d/m/Y') }}
                                </div>
                            </div>

                            <div class="text-right flex items-center gap-3">
                                <div class="font-bold text-green-600">Rp {{ number_format($trx->nominal, 0, ',', '.') }}</div>
                                
                                @if(Auth::user()->role === 'admin')
                                    <flux:button wire:click="editTransaksi({{ $trx->id }})" variant="ghost" size="sm" icon="pencil-square" class="text-indigo-600 hover:text-indigo-700 px-1" title="Edit Transaksi" />
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-sm text-zinc-500 py-4">Belum ada transaksi diinput hari ini.</div>
                    @endforelse
                </div>

                <flux:button class="mt-4" href="{{ route('transaksi.riwayat') }}" wire:navigate variant="subtle" icon="clock" class="w-full">
                    Lihat Semua Riwayat
                </flux:button>
            </flux:card>
        </div>
    </div>

	<flux:modal name="edit-transaksi" class="md:w-[450px]">
        <form wire:submit.prevent="updateTransaksi" class="space-y-5">
            <div class="border-b pb-3">
                <flux:heading size="lg">Edit Transaksi</flux:heading>
                <div class="text-xs text-zinc-500 mt-1">Admin Mode: Perbarui data jika terjadi kesalahan input.</div>
            </div>
            
            <div class="bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 text-sm space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-zinc-500 dark:text-zinc-400">Wajib Punia</span>
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $edit_nama_wp }}</span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-zinc-500 dark:text-zinc-400">Jenis Punia</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                        <span class="bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 px-1.5 py-0.5 rounded text-[10px] font-bold">
                            {{ $edit_jenis_pembayaran }}
                        </span>
                    </span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-zinc-500 dark:text-zinc-400">Periode Tagihan</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $edit_periode }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-zinc-500 dark:text-zinc-400">Tanggal Bayar</span>
                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                        {{ $edit_tanggal_bayar ? \Carbon\Carbon::parse($edit_tanggal_bayar)->translatedFormat('d F Y') : '-' }}
                    </span>
                </div>
            </div>
            
            <flux:field>
                <flux:label>Nominal Pembayaran</flux:label>
                <flux:input.group>
                    <flux:input.group.prefix>Rp</flux:input.group.prefix>
                    <flux:input type="number" wire:model="edit_nominal" required />
                </flux:input.group>
            </flux:field>
            
            <flux:textarea wire:model="edit_keterangan" label="Keterangan" rows="2" />
            
            <flux:field class="bg-zinc-50 dark:bg-zinc-800 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <flux:label class="mb-2">Perbarui Bukti Dokumen</flux:label>
                
                @if($edit_bukti_lama)
                    <div class="flex items-center gap-2 text-sm mb-3 bg-white dark:bg-zinc-900 p-2 rounded border border-zinc-200 dark:border-zinc-600">
                        <flux:icon.document-check class="w-4 h-4 text-green-500" />
                        <a href="{{ url('storage/' . $edit_bukti_lama) }}" target="_blank" class="text-indigo-600 hover:text-indigo-700 underline text-xs font-medium">Lihat Dokumen Saat Ini</a>
                    </div>
                @endif
                
                <flux:input type="file" wire:model="edit_bukti_baru" accept="image/*,.pdf" class="text-sm" />
                <div wire:loading wire:target="edit_bukti_baru" class="text-xs text-indigo-600 mt-1">Mengunggah file baru...</div>
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" x-on:click="$flux.modal('edit-transaksi').close()" variant="ghost">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan Perubahan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>