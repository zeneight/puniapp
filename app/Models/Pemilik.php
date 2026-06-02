<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pemilik extends Model
{
    protected $guarded = ['id'];

    // Relasi: Satu Pemilik bisa memiliki banyak tempat usaha (Wajib Punia)
    public function wajibPunias()
    {
        return $this->hasMany(WajibPunia::class);
    }
}