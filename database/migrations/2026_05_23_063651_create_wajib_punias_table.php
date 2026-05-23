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
        Schema::create('wajib_punias', function (Blueprint $table) {
            $table->id();
            $table->string('no_registrasi')->unique()->nullable();
            
            // Foreign Keys ke Master Data
            $table->foreignId('kategori_id')->constrained('kategoris')->restrictOnDelete();
            $table->foreignId('banjar_id')->constrained('banjars')->restrictOnDelete();
            
            // Data Utama
            $table->string('nama'); // Nama Usaha / Orang
            $table->text('alamat');
            $table->string('jenis_usaha')->nullable();
            $table->integer('jumlah_unit')->default(1);
            
            // Kontak & Pengelola
            $table->string('nama_pemilik')->nullable();
            $table->string('kontak_pemilik')->nullable();
            $table->string('kontak_pengelola')->nullable();
            
            // Pagu (Target Bayar)
            $table->integer('pagu_dudukan'); 
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wajib_punias');
    }
};
