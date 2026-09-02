<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KunjunganTamu extends Model
{
    protected $guarded = ['id'];

    public function tamu()
    {
        return $this->belongsTo(Tamu::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function banjar()
    {
        return $this->belongsTo(Banjar::class);
    }

    public function riwayats()
    {
        return $this->hasMany(RiwayatTindakLanjut::class, 'kunjungan_id');
    }
}