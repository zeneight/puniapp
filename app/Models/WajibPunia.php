<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WajibPunia extends Model
{
    protected $guarded = ['id']; // Membuka semua kolom agar bisa di-input massal

    // Definisikan relasi balik ke Banjar
    public function banjar(): BelongsTo
    {
        return $this->belongsTo(Banjar::class);
    }

    // Definisikan relasi balik ke Kategori
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    // Definisikan relasi balik ke User (petugas)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Master Pemilik
    // public function pemilik()
    // {
    //     return $this->belongsTo(Pemilik::class);
    // }

    // Relasi ke Master Jenis Usaha
    public function jenisUsaha()
    {
        return $this->belongsTo(JenisUsaha::class, 'jenis_usaha_id');
    }

    // Relasi ke Master user (petugas)
    public function petugas() {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke Tabel Dokumen Lampiran (Bisa lebih dari 1 file)
    public function dokumens()
    {
        return $this->hasMany(DokumenWajibPunia::class);
    }
}