<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->foreignId('jenis_pembayaran_id')->nullable()->after('wajib_punia_id')->constrained('kategoris')->restrictOnDelete();
            
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropForeign(['jenis_pembayaran_id']);
            $table->dropColumn(['jenis_pembayaran_id']);
        });
    }
};