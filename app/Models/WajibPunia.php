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
        return $this->belongsTo(Kategori::class);
    }

    // Definisikan relasi balik ke User (petugas)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}