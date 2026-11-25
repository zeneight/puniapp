<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WajibPunia extends Model
{
    use LogsActivity;

    protected $guarded = ['id']; // Membuka semua kolom agar bisa di-input massal

    // Definisikan relasi balik ke Banjar
    public function banjar(): BelongsTo
    {
        return $this->belongsTo(Banjar::class);
    }

    // Definisikan relasi balik ke Kategori
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    // Definisikan relasi balik ke User (petugas)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Master Pemilik
    // public function pemilik()
    // {
    //     return $this->belongsTo(Pemilik::class);
    // }

    // Relasi ke Master Jenis Usaha
    public function jenisUsaha()
    {
        return $this->belongsTo(JenisUsaha::class, 'jenis_usaha_id');
    }

    // Relasi ke Master user (petugas)
    public function petugas() {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke Tabel Dokumen Lampiran (Bisa lebih dari 1 file)
    public function dokumens()
    {
        return $this->hasMany(DokumenWajibPunia::class);
    }

    // Relasi ke Tabel Transaksi
    public function transaksi()
    {
        return $this->hasMany(Transaksi::class);
    }

    // aktivitas log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Wajib Punia') // Nama modul untuk filter di admin
            ->logAll() // Catat semua kolom
            ->logExcept(['updated_at', 'created_at']) // Abaikan timestamp
            ->logOnlyDirty() // Hanya catat kolom yang benar-benar berubah saat edit
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'Menambahkan data wajib punia baru',
                'updated' => 'Memperbarui data wajib punia',
                'deleted' => 'Menghapus data wajib punia',
                default   => "Aktivitas {$eventName} pada wajib punia",
            });
    }
}