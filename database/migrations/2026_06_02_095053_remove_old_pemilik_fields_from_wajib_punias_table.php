<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wajib_punias', function (Blueprint $table) {
            // Cek dan hapus nama_pemilik
            if (Schema::hasColumn('wajib_punias', 'nama_pemilik')) {
                $table->dropColumn('nama_pemilik');
            }
            
            // Cek dan hapus kontak_pemilik
            if (Schema::hasColumn('wajib_punias', 'kontak_pemilik')) {
                $table->dropColumn('kontak_pemilik');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wajib_punias', function (Blueprint $table) {
            // Rollback jika dibutuhkan
            if (!Schema::hasColumn('wajib_punias', 'nama_pemilik')) {
                $table->string('nama_pemilik')->nullable();
            }
            if (!Schema::hasColumn('wajib_punias', 'kontak_pemilik')) {
                $table->string('kontak_pemilik')->nullable();
            }
        });
    }
};