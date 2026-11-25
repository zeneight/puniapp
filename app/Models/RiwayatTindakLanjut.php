<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RiwayatTindakLanjut extends Model
{
    use LogsActivity;

    protected $fillable = ['kunjungan_id', 'status_log', 'catatan'];

    public function kunjungan()
    {
        return $this->belongsTo(KunjunganTamu::class, 'kunjungan_id');
    }

    // aktivitas log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Tindak Lanjut Tamu') // Nama modul untuk filter di admin
            ->logAll() // Catat semua kolom
            ->logExcept(['updated_at', 'created_at']) // Abaikan timestamp
            ->logOnlyDirty() // Hanya catat kolom yang benar-benar berubah saat edit
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Menambahkan data tindak lanjut tamu baru',
                'updated' => 'Memperbarui data tindak lanjut tamu',
                'deleted' => 'Menghapus data tindak lanjut tamu',
                default   => "Aktivitas {$eventName} pada tindak lanjut tamu",
            });
    }
}