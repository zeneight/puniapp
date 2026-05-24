<?php
use Illuminate\Support\Facades\Route;

use App\Livewire\Master\BanjarIndex;


Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // banjar
    Route::livewire('/master/banjar', 'master-banjar-index')->name('master.banjar');
    Route::livewire('/master/wajib-punia', 'master-wajib-punia-index')->name('master.wajibpunia');
});

require __DIR__.'/settings.php';