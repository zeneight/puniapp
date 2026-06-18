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
            // Tambahkan kolom pemilik_nama
            $table->string('pemilik_nama')->nullable()->after('is_active');
            
            // OPSIONAL: Jika kamu ingin menghapus relasi pemilik_id yang lama sama sekali
            // $table->dropForeign(['pemilik_id']);
            // $table->dropColumn('pemilik_id');
        });
    }

    public function down(): void
    {
        Schema::table('wajib_punias', function (Blueprint $table) {
            $table->dropColumn('pemilik_nama');
        });
    }
};
