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
        Schema::create('dokumen_wajib_punias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wajib_punia_id')->constrained('wajib_punias')->cascadeOnDelete();
            $table->string('nama_file'); // Contoh: "KTP", "Izin Usaha", "Foto Depan"
            $table->string('path_file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen_wajib_punias');
    }
};
