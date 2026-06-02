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
        Schema::table('wajib_punias', function (Blueprint $table) {
            // kolom baru
            $table->foreignId('pemilik_id')->nullable()->constrained('pemiliks')->nullOnDelete();
            $table->foreignId('jenis_usaha_id')->nullable()->constrained('jenis_usahas')->nullOnDelete();
            $table->date('tgl_registrasi')->nullable();
            $table->string('no_registrasi')->unique()->nullable();
            
            // Hapus kolom jenis_usaha lama (yang masih bertipe string)
            $table->dropColumn('jenis_usaha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wajib_punias', function (Blueprint $table) {
            //
            $table->dropForeign(['pemilik_id']);
            $table->dropForeign(['jenis_usaha_id']);
            $table->dropColumn(['pemilik_id', 'jenis_usaha_id', 'tgl_registrasi', 'no_registrasi']);
            $table->string('jenis_usaha')->nullable();
        });
    }
};
