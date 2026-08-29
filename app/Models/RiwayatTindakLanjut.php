<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatTindakLanjut extends Model
{
    protected $fillable = ['kunjungan_id', 'status_log', 'catatan'];

    public function kunjungan()
    {
        return $this->belongsTo(KunjunganTamu::class, 'kunjungan_id');
    }
}