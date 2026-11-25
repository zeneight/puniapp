<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Transaksi extends Model
{
    use LogsActivity;
    
    //
    protected $guarded = ['id'];

    // relasi ke WajibPunia
    public function wajibPunia()
    {
        return $this->belongsTo(WajibPunia::class, 'wajib_punia_id');
    }

    // Relasi ke User (Petugas Input)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke JenisPembayaran
    public function jenisPembayaran(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'jenis_pembayaran_id');
    }

    // aktivitas log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Transaksi') // Nama modul untuk filter di admin
            ->logAll() // Catat semua kolom
            ->logExcept(['updated_at', 'created_at']) // Abaikan timestamp
            ->logOnlyDirty() // Hanya catat kolom yang benar-benar berubah saat edit
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Menambahkan data transaksi baru',
                'updated' => 'Memperbarui data transaksi',
                'deleted' => 'Menghapus data transaksi',
                default   => "Aktivitas {$eventName} pada transaksi",
            });
    }
}
