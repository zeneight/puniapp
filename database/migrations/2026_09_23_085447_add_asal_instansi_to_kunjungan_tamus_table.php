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
        Schema::table('kunjungan_tamus', function (Blueprint $table) {
            // Tambahkan kolom asal_instansi. 
            // nullable() berarti boleh kosong jika tamu tidak memiliki instansi.
            // after('alasan_kunjungan') agar posisinya rapi di database.
            $table->string('asal_instansi')->nullable()->after('alasan_kunjungan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kunjungan_tamus', function (Blueprint $table) {
            // Hapus kolom jika migration di-rollback
            $table->dropColumn('asal_instansi');
        });
    }
};