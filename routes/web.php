<?php
use Illuminate\Support\Facades\Route;

use App\Livewire\Master\BanjarIndex;


Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/dashboard', 'dashboard-index')->name('dashboard');

    // master data
    Route::middleware(['role:admin'])->group(function () {
        Route::livewire('/master/banjar', 'master-banjar-index')->name('master.banjar');
        Route::livewire('/master/wajib-punia', 'master-wajib-punia-index')->name('master.wajibpunia');
        Route::livewire('/master/jenis-usaha', 'master-jenis-usaha-index')->name('master.jenisusaha');
        Route::livewire('/master/kategori', 'master-kategori-index')->name('master.kategori');
        Route::livewire('/master/pemilik', 'master-pemilik-index')->name('master.pemilik');
        Route::livewire('/master/user', 'manajemen-user-index')->name('master.user');
    });

    // transaksi
    Route::livewire('/transaksi/input', 'transaksi-input-punia')->name('transaksi.input');
    Route::livewire('/transaksi/riwayat', 'transaksi-riwayat')->name('transaksi.riwayat');
});

require __DIR__.'/settings.php';