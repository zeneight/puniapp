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
            // Menambahkan kolom user_id (petugas) yang boleh kosong jika belum ditugaskan
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('kategori_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wajib_punias', function (Blueprint $table) {
            //
        });
    }
};
