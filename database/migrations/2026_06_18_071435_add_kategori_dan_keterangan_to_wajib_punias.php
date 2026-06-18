<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wajib_punias', function (Blueprint $table) {
            // Tambahkan kolom keterangan
            $table->text('keterangan')->nullable()->after('pemilik_nama');
            
            // Tambahkan relasi ke tabel kategoris
            // Menggunakan nullOnDelete agar data Wajib Punia tidak ikut terhapus jika Master Kategori dihapus
            $table->foreignId('kategori_id')->nullable()->after('jenis_usaha_id')->constrained('kategoris')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wajib_punias', function (Blueprint $table) {
            // Hapus relasi (foreign key) terlebih dahulu
            $table->dropForeign(['kategori_id']);
            
            // Baru hapus kolomnya
            $table->dropColumn(['kategori_id', 'keterangan']);
        });
    }
};