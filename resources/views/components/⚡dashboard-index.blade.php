<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    #[Layout('layouts.app')]

    public function with()
    {
        // Hanya menyisakan query untuk 5 transaksi terakhir
        $queryTransaksi = Transaksi::with(['wajib_punia', 'user'])->latest()->take(5);

        if (Auth::user()->role === 'inputer') {
            $queryTransaksi->where('user_id', Auth::id());
        }

        return [
            'transaksiTerbaru' => $queryTransaksi->get(),
        ];
    }
};
?>

<div>
    <div class="mb-6">
        <flux:heading size="xl">Dashboard Sistem Punia</flux:heading>
        <flux:subheading>Ringkasan penerimaan dana dan aktivitas sistem saat ini.</flux:subheading>
    </div>

    <livewire:dashboard-widget-statistik />

    <div class="mb-8">
        <livewire:dashboard-grafik-pendapatan />
    </div>

    <flux:card>
        <div class="mb-4">
            <flux:heading size="lg">5 Transaksi Masuk Terakhir</flux:heading>
        </div>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Tanggal</flux:table.column>
                <flux:table.column>Wajib Punia</flux:table.column>
                <flux:table.column>Periode</flux:table.column>
                <flux:table.column>Nominal</flux:table.column>
                <flux:table.column>Petugas</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($transaksiTerbaru as $trx)
                <flux:table.row>
                    <flux:table.cell>{{ \Carbon\Carbon::parse($trx->tanggal_bayar)->format('d M Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="font-semibold">{{ $trx->wajib_punia->nama ?? 'Terhapus' }}</div>
                    </flux:table.cell>
                    <flux:table.cell>Bulan {{ $trx->periode_bulan }} / {{ $trx->periode_tahun }}</flux:table.cell>
                    <flux:table.cell class="font-mono text-green-600">Rp {{ number_format($trx->nominal, 0, ',', '.') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm">{{ $trx->user->name ?? '-' }}</flux:badge>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-500">Belum ada data transaksi masuk.</flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>