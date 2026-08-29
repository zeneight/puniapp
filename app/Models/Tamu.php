<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tamu extends Model
{
    protected $fillable = ['nama_pengunjung', 'kontak_wa', 'asal_instansi', 'pekerjaan_status'];

    public function kunjungans()
    {
        return $this->hasMany(KunjunganTamu::class);
    }
}