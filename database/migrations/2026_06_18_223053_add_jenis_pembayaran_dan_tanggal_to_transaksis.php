<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            // Cek dulu apakah kolom jenis_pembayaran_id sudah ada
            if (!Schema::hasColumn('transaksis', 'jenis_pembayaran_id')) {
                $table->foreignId('jenis_pembayaran_id')->nullable()->after('wajib_punia_id')->constrained('kategoris')->restrictOnDelete();
            }
            
            // Cek dulu apakah kolom tanggal_bayar sudah ada
            if (!Schema::hasColumn('transaksis', 'tanggal_bayar')) {
                $table->date('tanggal_bayar')->nullable()->after('periode_tahun');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropForeign(['jenis_pembayaran_id']);
            $table->dropColumn(['jenis_pembayaran_id']);
        });
    }
};