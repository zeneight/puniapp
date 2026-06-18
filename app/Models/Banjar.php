<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banjar extends Model
{
    //
    protected $fillable = [
        'nama_banjar',
    ];

    public function wajibPunias()
    {
        return $this->hasMany(WajibPunia::class, 'banjar_id');
    }
}
