<?php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Banjar;
use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Akun Admin & Inputer
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@punia.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'degung (Petugas)',
            'email' => 'degung@punia.com',
            'password' => Hash::make('password'),
            'role' => 'inputer',
        ]);

        // 2. Buat Master Kategori (Berdasarkan Spreadsheet)
        Kategori::create(['nama_kategori' => 'Dudukan Usaha', 'deskripsi' => 'Punia bulanan untuk tempat usaha']);
        Kategori::create(['nama_kategori' => 'Domisili/SKTT', 'deskripsi' => 'Punia bulanan untuk warga domisili']);

        // 3. Buat Master Banjar (Berdasarkan Spreadsheet)
        $banjars = ['Sedahan Munggu', 'Dukuh Sengguan', 'Pempatan'];
        foreach ($banjars as $banjar) {
            Banjar::create(['nama_banjar' => $banjar]);
        }
    }
}