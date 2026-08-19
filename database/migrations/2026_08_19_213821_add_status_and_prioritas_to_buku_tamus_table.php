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
        Schema::table('buku_tamus', function (Blueprint $table) {
            // Kita taruh posisinya di bawah kolom tindak_lanjut
            // Diberi default agar data buku tamu yang lama tidak error / bernilai null
            $table->string('status', 50)->default('Tamu masuk')->after('tindak_lanjut');
            $table->string('prioritas', 50)->default('Prioritas 3')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buku_tamus', function (Blueprint $table) {
            $table->dropColumn(['status', 'prioritas']);
        });
    }
};