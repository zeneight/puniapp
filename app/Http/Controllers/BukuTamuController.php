<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BukuTamu;

class BukuTamuController extends Controller
{
    public function cetak($tamu_id)
    {
        // Tarik data Tamu beserta seluruh kunjungan dan riwayatnya
        $tamu = \App\Models\Tamu::with(['kunjungans' => function($q) {
            $q->orderBy('tanggal_kunjungan', 'desc') // Kunjungan terbaru di atas
              ->with(['banjar', 'riwayats' => function($q2) {
                  $q2->orderBy('created_at', 'asc'); // Riwayat timeline dari awal ke akhir
              }]);
        }])->findOrFail($tamu_id);

        return view('cetak.buku-tamu-v2', compact('tamu'));
    }

}