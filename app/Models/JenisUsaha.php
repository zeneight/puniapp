<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisUsaha extends Model
{
    // Mengizinkan semua kolom diisi secara massal kecuali ID
    protected $guarded = ['id'];

    // Relasi: Satu Jenis Usaha bisa dimiliki oleh banyak Wajib Punia
    public function wajibPunias()
    {
        return $this->hasMany(WajibPunia::class);
    }
}