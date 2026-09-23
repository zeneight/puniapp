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
        Schema::create('detail_kunjungan_tamu', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke tabel kunjungan (sesuaikan 'kunjungans' dengan nama tabel asli Bli)
            $table->foreignId('kunjungan_id')
                ->constrained('kunjungan_tamus') 
                ->onDelete('cascade');

            // Relasi ke tabel tamu (sesuaikan 'master_tamus' dengan nama tabel asli Bli)
            $table->foreignId('tamu_id')
                ->constrained('tamus')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kunjungan_tamu');
    }
};
