<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\Component;

new #[Title('Pencadangan Sistem')] class extends Component {
    
    // Fungsi Menjalankan Backup Baru
    public function runBackup()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        try {
            Artisan::call('backup:run', ['--only-db' => true]);
            Flux::toast(variant: 'success', text: 'Backup database berhasil dijalankan!');
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: 'Gagal: ' . $e->getMessage());
        }
    }

    // Fungsi Menghapus File Backup di Server
    public function deleteBackup($filename)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $disk = Storage::disk('local');
        $files = $disk->allFiles();
        $path = collect($files)->first(fn($file) => basename($file) === $filename);

        if ($path && $disk->exists($path)) {
            $disk->delete($path);
            Flux::toast(variant: 'success', text: 'File backup berhasil dihapus.');
        }
    }

    // Mengambil daftar file .zip backup yang tersimpan di server
    #[Computed]
    public function backupFiles()
    {
        $disk = Storage::disk('local');
        $files = $disk->allFiles();

        // Ambil hanya file yang berformat .zip
        $backups = [];
        foreach ($files as $file) {
            if (str_ends_with($file, '.zip')) {
                $backups[] = [
                    'filename' => basename($file),
                    'path' => $file,
                    'size' => $disk->size($file),
                    'last_modified' => $disk->lastModified($file),
                ];
            }
        }

        // Urutkan dari yang paling baru
        usort($backups, fn($a, $b) => $b['last_modified'] <=> $a['last_modified']);

        return $backups;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">Pencadangan Sistem</flux:heading>

    <x-pages::settings.layout heading="Pencadangan Sistem" subheading="Kelola dan unduh cadangan (backup) database aplikasi secara manual.">
        
        <div class="my-6 space-y-6">
            
            <!-- Tombol Buat Backup -->
            <div class="bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 p-5 rounded-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
                <div>
                    <div class="font-semibold text-zinc-900 dark:text-white">Buat Cadangan Database Baru</div>
                    <div class="text-sm text-zinc-500 mt-1">Sistem akan mengompilasi data terbaru ke dalam format `.zip`.</div>
                </div>
                <flux:button wire:click="runBackup" variant="primary" icon="circle-stack" class="shrink-0">
                    Jalankan Backup Sekarang
                </flux:button>
            </div>

            <!-- Daftar File Backup yang Tersedia di Server -->
            <div>
                <flux:heading size="md" class="mb-3">Daftar File Backup di Server</flux:heading>
                
                <div class="overflow-x-auto border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                            <tr>
                                <th class="px-4 py-3 font-medium">Nama File</th>
                                <th class="px-4 py-3 font-medium">Ukuran</th>
                                <th class="px-4 py-3 font-medium">Tanggal Dibuat</th>
                                <th class="px-4 py-3 font-medium text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 text-zinc-700 dark:text-zinc-300">
                            @forelse($this->backupFiles as $backup)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $backup['filename'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ number_format($backup['size'] / 1048576, 2) }} MB</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ \Carbon\Carbon::createFromTimestamp($backup['last_modified'])->translatedFormat('d M Y, H:i') }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                                    <!-- Tombol Download -->
                                    <flux:button href="{{ route('settings.backup.download', $backup['filename']) }}" size="sm" variant="outline" icon="arrow-down-tray">
                                        Unduh
                                    </flux:button>
                                    
                                    <!-- Tombol Hapus File Server -->
                                    <flux:button wire:click="deleteBackup('{{ $backup['filename'] }}')" wire:confirm="Yakin ingin menghapus file backup ini dari server?" size="sm" variant="ghost" color="danger" icon="trash" />
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-zinc-500 italic">
                                    Belum ada file backup tersimpan. Silakan klik "Jalankan Backup Sekarang".
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </x-pages::settings.layout>
</section>