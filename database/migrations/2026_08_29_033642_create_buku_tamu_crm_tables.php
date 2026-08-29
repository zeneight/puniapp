<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. TABEL MASTER TAMU
        Schema::create('tamus', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pengunjung');
            $table->string('kontak_wa', 50)->nullable();
            $table->string('asal_instansi')->nullable();
            $table->string('pekerjaan_status')->nullable();
            $table->timestamps();
        });

        // 2. TABEL TRANSAKSI KUNJUNGAN
        Schema::create('kunjungan_tamus', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel Master Tamu
            $table->foreignId('tamu_id')->constrained('tamus')->cascadeOnDelete();
            
            // Relasi ke tabel User (Petugas yang login & mencatat)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Relasi ke Master Banjar (Opsional, jika banjar dihapus, data tidak hilang)
            $table->foreignId('banjar_id')->nullable()->constrained('banjars')->nullOnDelete();
            
            $table->date('tanggal_kunjungan');
            $table->string('petugas')->nullable(); // Nama petugas manual yang menangani
            $table->text('alasan_kunjungan');
            $table->string('prioritas', 50)->default('Prioritas 3');
            $table->string('status', 50)->default('Tamu masuk');
            $table->integer('kunjungan_ke')->default(1);
            
            $table->timestamps();
        });

        // 3. TABEL RIWAYAT / TIMELINE TINDAK LANJUT
        Schema::create('riwayat_tindak_lanjuts', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel Kunjungan
            $table->foreignId('kunjungan_id')->constrained('kunjungan_tamus')->cascadeOnDelete();
            
            $table->string('status_log', 50); // Proses / Selesai
            $table->text('catatan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop tabel harus dibalik urutannya (dari anak ke induk) agar tidak error foreign key
        Schema::dropIfExists('riwayat_tindak_lanjuts');
        Schema::dropIfExists('kunjungan_tamus');
        Schema::dropIfExists('tamus');
    }
};