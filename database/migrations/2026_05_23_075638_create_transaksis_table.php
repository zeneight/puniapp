<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            
            // Relasi Utama
            $table->foreignId('wajib_punia_id')->constrained('wajib_punias')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete(); // Petugas yg input
            
            // Periode untuk Filter Grafik Dashboard
            $table->integer('periode_bulan');
            $table->integer('periode_tahun');
            
            // Data Pembayaran
            $table->integer('nominal');
            $table->date('tanggal_bayar');
            $table->text('keterangan')->nullable();
            $table->string('bukti_dokumen')->nullable();
            
            $table->timestamps();
            
            // Mencegah double input untuk Wajib Punia yg sama di bulan & tahun yg sama
            $table->unique(['wajib_punia_id', 'periode_bulan', 'periode_tahun'], 'unik_transaksi_periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
