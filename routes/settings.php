<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');

    Route::livewire('settings/history', 'pages::settings.history')->name('settings.history');
    Route::livewire('settings/backup', 'pages::settings.backup')->name('settings.backup');

    // Route untuk mendownload file backup
    Route::get('/settings/backup/download/{filename}', function ($filename) {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        // Cari file di disk 'local' (default penyimpanan spatie-backup)
        $disk = Storage::disk('local');
        $files = $disk->allFiles();
        
        // Cocokkan nama file
        $path = collect($files)->first(fn($file) => basename($file) === $filename);

        if ($path && $disk->exists($path)) {
            return $disk->download($path);
        }

        abort(404, 'File backup tidak ditemukan.');
    })->name('settings.backup.download')->middleware(['auth']);

 });
