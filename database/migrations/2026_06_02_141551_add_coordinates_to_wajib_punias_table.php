<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wajib_punias', function (Blueprint $table) {
            // Menggunakan decimal(10, 8) dan (11, 8) adalah standar baku untuk akurasi GPS tinggi
            $table->decimal('latitude', 10, 8)->nullable()->after('alamat');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('wajib_punias', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};