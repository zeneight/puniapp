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
        // 1. Cek & Tambah pemilik_id
        if (!Schema::hasColumn('wajib_punias', 'pemilik_id')) {
            Schema::table('wajib_punias', function (Blueprint $table) {
                $table->foreignId('pemilik_id')->nullable()->constrained('pemiliks')->nullOnDelete();
            });
        }

        // 2. Cek & Tambah jenis_usaha_id
        if (!Schema::hasColumn('wajib_punias', 'jenis_usaha_id')) {
            Schema::table('wajib_punias', function (Blueprint $table) {
                $table->foreignId('jenis_usaha_id')->nullable()->constrained('jenis_usahas')->nullOnDelete();
            });
        }

        // 3. Cek & Tambah tgl_registrasi
        if (!Schema::hasColumn('wajib_punias', 'tgl_registrasi')) {
            Schema::table('wajib_punias', function (Blueprint $table) {
                $table->date('tgl_registrasi')->nullable();
            });
        }

        // 4. Cek & Tambah no_registrasi
        if (!Schema::hasColumn('wajib_punias', 'no_registrasi')) {
            Schema::table('wajib_punias', function (Blueprint $table) {
                $table->string('no_registrasi')->unique()->nullable();
            });
        }

        // 5. Cek & Hapus kolom jenis_usaha (yang lama/string)
        if (Schema::hasColumn('wajib_punias', 'jenis_usaha')) {
            Schema::table('wajib_punias', function (Blueprint $table) {
                $table->dropColumn('jenis_usaha');
            });
        }
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
