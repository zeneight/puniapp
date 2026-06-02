<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wajib_punias', function (Blueprint $table) {
            // 1. Putus dulu tali relasi foreign key-nya
            $table->dropForeign(['kategori_id']); 
            
            // 2. Setelah putus, baru kolomnya aman untuk dihanguskan
            $table->dropColumn('kategori_id');
        });
    }

    public function down(): void
    {
        Schema::table('wajib_punias', function (Blueprint $table) {
            $table->foreignId('kategori_id')->nullable();
        });
    }
};