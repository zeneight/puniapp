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
            $table->string('latitude')->nullable()->after('alasan_kunjungan'); // Sesuaikan posisi 'after' dengan kolom Bli
            $table->string('longitude')->nullable()->after('latitude');
            $table->string('lampiran')->nullable()->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kunjungan_tamus', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'lampiran']);
        });
    }
};
