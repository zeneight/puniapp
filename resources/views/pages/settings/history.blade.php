<?php

use App\Models\KunjunganTamu;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Log History')] class extends Component {
    #[Computed]
    public function activityLogs()
    {
        $kunjungan = KunjunganTamu::where('user_id', Auth::id())
                        ->selectRaw("'Mencatat Kunjungan Tamu' as aksi, created_at")
                        ->latest()->limit(10);
                        
        $transaksi = Transaksi::where('user_id', Auth::id())
                        ->selectRaw("'Menerima Pembayaran Punia' as aksi, created_at")
                        ->latest()->limit(10);
        
        return $kunjungan->union($transaksi)->orderBy('created_at', 'desc')->get();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Log History') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Log History')" :subheading="__('Daftar riwayat aktivitas terakhir yang Anda lakukan.')">
        <div class="my-6 overflow-x-auto border border-zinc-200 dark:border-zinc-700 rounded-lg">
            <table class="w-full text-sm text-left">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 font-medium">Waktu / Tanggal</th>
                        <th class="px-4 py-3 font-medium">Aktivitas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 text-zinc-700 dark:text-zinc-300">
                    @forelse($this->activityLogs as $log)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($log->created_at)->translatedFormat('d M Y, H:i') }}</td>
                        <td class="px-4 py-3">{{ $log->aksi }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="px-4 py-8 text-center text-zinc-500">Belum ada riwayat aktivitas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-pages::settings.layout>
</section>