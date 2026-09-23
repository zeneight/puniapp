<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tamu extends Model
{
    protected $fillable = ['nama_pengunjung', 'kontak_wa', 'asal_instansi', 'pekerjaan_status'];

    // public function kunjungans()
    // {
    //     return $this->hasMany(KunjunganTamu::class);
    // }

    public function kunjungans()
    {
        return $this->belongsToMany(KunjunganTamu::class, 'detail_kunjungan_tamu', 'tamu_id', 'kunjungan_tamu_id');
    }
}