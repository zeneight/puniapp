<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaksi extends Model
{
    //
    protected $guarded = ['id'];

    // relasi ke WajibPunia
    public function wajib_punia()
    {
        return $this->belongsTo(WajibPunia::class, 'wajib_punia_id');
    }

    // Relasi ke User (Petugas Input)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
