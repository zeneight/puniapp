<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Riwayat Kunjungan - {{ $tamu->nama_pengunjung }}</title>
    @vite(['resources/css/app.css'])
    <style>
        /* CSS Khusus agar rapi saat dicetak di kertas A4 */
        @media print {
            @page { size: A4; margin: 1.5cm; }
            .no-print { display: none !important; }
            body { 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
                background-color: white; 
            }
        }
    </style>
</head>
<body class="bg-white text-black font-sans antialiased text-[13px]">
    
    <div class="max-w-4xl mx-auto p-8 bg-white">
        
        <div class="no-print flex justify-end mb-6">
            <button onclick="window.print()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700 flex items-center gap-2 font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak Formulir
            </button>
        </div>

        <div class="border-b-[3px] border-black pb-3 mb-5 text-center relative flex flex-col items-center">
            <h1 class="text-xl font-bold uppercase tracking-widest mb-0.5">Pengelola Desa Wisata Munggu</h1>
            <h2 class="text-sm font-bold uppercase mb-1">Kecamatan Mengwi, Kabupaten Badung</h2>
            <p class="text-[10px] leading-tight">Alamat : Jl Nakula, Br Pempatan Desa Munggu. Email : ds.wisata.munggu@gmail.com</p>
            <p class="text-[10px] leading-tight">Whatsapp : 0822-5888-6394, Website : www.dswisatamunggu.com</p>
        </div>

    <h3 class="text-center font-bold text-lg underline mb-6">LAPORAN RIWAYAT KUNJUNGAN TAMU</h3>

    <!-- INFO MASTER TAMU -->
    <table class="w-full mb-8 font-sans text-sm">
        <tr>
            <td class="py-1 w-1/4 font-semibold">Nama Pengunjung</td>
            <td class="py-1 w-[2%]">:</td>
            <td class="py-1 uppercase font-bold">{{ $tamu->nama_pengunjung }}</td>
        </tr>
        <tr>
            <td class="py-1 font-semibold">No. Kontak / WA</td>
            <td class="py-1">:</td>
            <td class="py-1">{{ $tamu->kontak_wa ?? '-' }}</td>
        </tr>
        <tr>
            <td class="py-1 font-semibold">Pekerjaan / Jabatan</td>
            <td class="py-1">:</td>
            <td class="py-1">{{ $tamu->pekerjaan_status ?? '-' }}</td>
        </tr>
        <tr>
            <td class="py-1 font-semibold">Asal Instansi / Alamat</td>
            <td class="py-1">:</td>
            <td class="py-1">{{ $tamu->asal_instansi ?? '-' }}</td>
        </tr>
    </table>

    <!-- DAFTAR KUNJUNGAN & TIMELINE -->
    <div class="font-sans">
        <h4 class="font-bold text-base mb-3 border-b-2 border-gray-300 pb-1">Daftar Kunjungan & Tindak Lanjut</h4>
        
        @forelse($tamu->kunjungans as $kunjungan)
            <div class="mb-6 break-inside-avoid border border-gray-300 p-4 rounded-lg bg-gray-50">
                <!-- Info Kunjungan Utama -->
                <div class="flex justify-between items-start mb-3 border-b border-gray-200 pb-3">
                    <div>
                        <div class="font-bold text-lg">Kunjungan ke-{{ $kunjungan->kunjungan_ke }}</div>
                        <div class="text-xs text-gray-600 mt-1">
                            {{ \Carbon\Carbon::parse($kunjungan->tanggal_kunjungan)->translatedFormat('l, d F Y') }}
                        </div>
                    </div>
                    <div class="text-right text-xs">
                        <div><span class="font-semibold">Status:</span> {{ $kunjungan->status }}</div>
                        <div><span class="font-semibold">Petugas:</span> {{ $kunjungan->petugas }}</div>
                        <div><span class="font-semibold">Lokasi:</span> {{ $kunjungan->banjar->nama_banjar ?? '-' }}</div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="font-semibold text-sm mb-1">Maksud Kunjungan:</div>
                    <div class="text-sm italic">"{{ $kunjungan->alasan_kunjungan }}"</div>
                </div>

                <!-- Timeline / Log Tindak Lanjut -->
                <div class="mt-4">
                    <div class="font-semibold text-sm mb-2 text-gray-700">Riwayat Penanganan:</div>
                    <table class="w-full text-sm border-collapse">
                        @foreach($kunjungan->riwayats as $log)
                        <tr>
                            <td class="py-1.5 w-32 align-top text-xs text-gray-500 whitespace-nowrap">
                                {{ $log->created_at->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="py-1.5 w-24 align-top">
                                <span class="font-bold {{ $log->status_log == 'Selesai' ? 'text-green-700' : 'text-blue-700' }}">
                                    [{{ $log->status_log }}]
                                </span>
                            </td>
                            <td class="py-1.5 align-top">
                                {{ $log->catatan }}
                            </td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @empty
            <p class="text-gray-500 text-center italic py-4">Belum ada data kunjungan yang tercatat.</p>
        @endforelse
    </div>

    <!-- TANDA TANGAN (Opsional, letaknya di paling bawah halaman) -->
    <div class="mt-16 flex justify-end font-sans break-inside-avoid">
        <div class="text-center w-64">
            <p class="mb-16">Denpasar, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
            <p class="font-bold underline">Petugas / Admin Sistem</p>
            <p class="text-xs mt-1">NIP. ........................................</p>
        </div>
    </div>

</body>
</html>