<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BukuTamu extends Model
{
    //
    protected $guarded = ['id'];

    // Definisikan relasi balik ke User (petugas)
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
