<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class KunjunganTamu extends Model
{
    use LogsActivity;
    
    protected $guarded = ['id'];

    public function tamu()
    {
        // belongsToMany(ModelTujuan, NamaTabelPivot, ForeignKunciIni, ForeignKunciTujuan)
        return $this->belongsToMany(Tamu::class, 'detail_kunjungan_tamu', 'kunjungan_id', 'tamu_id');
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

    // aktivitas log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Kunjungan Tamu') // Nama modul untuk filter di admin
            ->logAll() // Catat semua kolom
            ->logExcept(['updated_at', 'created_at']) // Abaikan timestamp
            ->logOnlyDirty() // Hanya catat kolom yang benar-benar berubah saat edit
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Menambahkan data kunjungan tamu baru',
                'updated' => 'Memperbarui data kunjungan tamu',
                'deleted' => 'Menghapus data kunjungan tamu',
                default   => "Aktivitas {$eventName} pada kunjungan tamu",
            });
    }
}