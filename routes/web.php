<?php
use Illuminate\Support\Facades\Route;
use App\Models\BukuTamu;

use App\Livewire\Master\BanjarIndex;


Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/dashboard', 'dashboard-index')->name('dashboard');

    // wajib punia
    Route::livewire('/master/wajib-punia', 'master-wajib-punia-index')->name('master.wajibpunia');

    // master data
    Route::middleware(['role:admin'])->group(function () {
        Route::livewire('/master/banjar', 'master-banjar-index')->name('master.banjar');
        Route::livewire('/master/jenis-usaha', 'master-jenis-usaha-index')->name('master.jenisusaha');
        Route::livewire('/master/kategori', 'master-kategori-index')->name('master.kategori');
        Route::livewire('/master/pemilik', 'master-pemilik-index')->name('master.pemilik');
        Route::livewire('/master/user', 'manajemen-user-index')->name('master.user');
    });

    // transaksi
    Route::livewire('/transaksi/input', 'transaksi-input-punia')->name('transaksi.input');
    Route::livewire('/transaksi/riwayat', 'transaksi-riwayat')->name('transaksi.riwayat');

    // buku tamu
    Route::livewire('/buku-tamu', 'buku-tamu-index')->name('buku-tamu');
        
    Route::get('/buku-tamu/cetak/{nama}', function ($nama) {
        $nama_pengunjung = urldecode($nama);
        
        // Tarik semua riwayat kunjungan orang tersebut, urutkan dari yang paling awal
        $riwayatKunjungan = BukuTamu::with('user')
            ->where('nama_pengunjung', $nama_pengunjung)
            ->orderBy('tanggal_kunjungan', 'asc')
            ->get();

        return view('cetak.buku-tamu', compact('nama_pengunjung', 'riwayatKunjungan'));
    })->name('buku-tamu.cetak')->middleware('auth');
});

require __DIR__.'/settings.php';