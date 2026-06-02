<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DokumenWajibPunia extends Model
{
    protected $guarded = ['id'];

    // Relasi: Satu Dokumen hanya milik satu Wajib Punia
    public function wajibPunia()
    {
        return $this->belongsTo(WajibPunia::class);
    }
}