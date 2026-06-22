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
		Schema::create('buku_tamus', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // "Yang Menerima" (Petugas Login)
			
			$table->date('tanggal_kunjungan');
			$table->string('nama_pengunjung');
			$table->string('asal_instansi')->nullable(); // Alamat Pengunjung/Usaha/Proyek
			$table->string('kontak_wa')->nullable();
			$table->string('pekerjaan_status')->nullable();
			
			$table->text('alasan_kunjungan');
			$table->text('tindak_lanjut')->nullable(); // Bisa diisi nanti saat diedit
			
			$table->integer('kunjungan_ke')->default(1); // Otomatis dihitung sistem nanti
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('buku_tamus');
	}
};
