<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Riwayat Kunjungan - {{ $nama_pengunjung }}</title>
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
            <div class="text-[10px] italic mb-1 text-gray-600">Om Swastyastu</div>
            <h1 class="text-xl font-bold uppercase tracking-widest mb-0.5">Pengelola Desa Wisata Munggu</h1>
            <h2 class="text-sm font-bold uppercase mb-1">Kecamatan Mengwi, Kabupaten Badung</h2>
            <p class="text-[10px] leading-tight">Alamat : Jl Nakula, Br Pempatan Desa Munggu. Email : ds.wisata.munggu@gmail.com</p>
            <p class="text-[10px] leading-tight">Whatsapp : 0822-5888-6394, Website : www.desamunggu.com</p>
        </div>

        <h3 class="text-center text-base font-bold uppercase mb-6 underline underline-offset-4 tracking-wider">
            Daftar Kunjungan Dan Keperluan
        </h3>

        <div class="mb-3 font-bold text-sm">
            Nama Pengunjung : <span class="uppercase">{{ $nama_pengunjung }}</span>
        </div>

        <table class="w-full border-collapse border border-black text-sm">
            <thead>
                <tr class="bg-[#e6e6fa]"> <th class="border border-black px-4 py-2 w-1/3 text-center uppercase tracking-wide">Hari, Tanggal</th>
                    <th class="border border-black px-4 py-2 w-2/3 text-center uppercase tracking-wide">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($riwayatKunjungan as $index => $kunjungan)
                <tr>
                    <td class="border border-black px-4 py-3 align-top">
                        <div class="font-bold mb-3">Kunjungan {{ $index + 1 }}</div>
                        <div class="mb-1">Hari, Tanggal :</div>
                        <div class="font-medium">{{ \Carbon\Carbon::parse($kunjungan->tanggal_kunjungan)->translatedFormat('l, d F Y') }}</div>
                    </td>
                    <td class="border border-black px-4 py-3 align-top leading-relaxed">
                        <table class="w-full">
                            <tr><td class="w-48 py-1">Yang Menerima</td><td class="px-2 w-2">:</td><td class="font-medium">{{ $kunjungan->user->name ?? '-' }}</td></tr>
                            <tr><td class="py-1">Alamat Pengunjung/Usaha</td><td class="px-2 w-2">:</td><td class="font-medium">{{ $kunjungan->asal_instansi ?? '-' }}</td></tr>
                            <tr><td class="py-1">No Kontak WA</td><td class="px-2 w-2">:</td><td class="font-medium">{{ $kunjungan->kontak_wa ?? '-' }}</td></tr>
                            <tr><td class="py-1">Pekerjaan/status</td><td class="px-2 w-2">:</td><td class="font-medium">{{ $kunjungan->pekerjaan_status ?? '-' }}</td></tr>
                            <tr><td class="py-1 align-top">Alasan datang/Keterangan</td><td class="px-2 w-2 align-top">:</td><td class="font-medium align-top">{{ $kunjungan->alasan_kunjungan }}</td></tr>
                            <tr><td class="py-1 align-top mt-2 block">Tindak Lanjut</td><td class="px-2 w-2 align-top mt-2 block">:</td><td class="font-medium align-top mt-2 block">{{ $kunjungan->tindak_lanjut ?? '....................................................................' }}</td></tr>
                        </table>
                    </td>
                </tr>
                @endforeach

                @for($i = $riwayatKunjungan->count(); $i < 4; $i++)
                <tr>
                    <td class="border border-black px-4 py-3 align-top">
                        <div class="font-bold mb-3">Kunjungan {{ $i + 1 }}</div>
                        <div>Hari, Tanggal :</div>
                        <div class="mt-4 border-b border-dotted border-gray-400 w-3/4"></div>
                    </td>
                    <td class="border border-black px-4 py-3 align-top leading-relaxed text-gray-500">
                        <table class="w-full">
                            <tr><td class="w-48 py-1">Yang Menerima</td><td class="px-2 w-2">:</td><td>....................................................................</td></tr>
                            <tr><td class="py-1">Alamat Pengunjung/Usaha</td><td class="px-2 w-2">:</td><td>....................................................................</td></tr>
                            <tr><td class="py-1">No Kontak WA</td><td class="px-2 w-2">:</td><td>....................................................................</td></tr>
                            <tr><td class="py-1">Pekerjaan/status</td><td class="px-2 w-2">:</td><td>....................................................................</td></tr>
                            <tr><td class="py-1 align-top">Alasan datang/Keterangan</td><td class="px-2 w-2 align-top">:</td><td><br>....................................................................</td></tr>
                            <tr><td class="py-1 align-top mt-2 block">Tindak Lanjut</td><td class="px-2 w-2 align-top mt-2 block">:</td><td><br>....................................................................</td></tr>
                        </table>
                    </td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>

</body>
</html>