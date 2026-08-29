<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desa Wisata Munggu - Harmony, Tradition & Serenity</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        baligold: '#F3B431',
                        baligoldlight: '#FDE08A',
                        balired: '#A91B0D',
                        darkbg: '#070A0F'
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        serif: ['"Cinzel"', 'serif']
                    }
                }
            }
        }
    </script>
    
    <style>
        .hero-bg {
            background: linear-gradient(180deg, rgba(7, 10, 15, 0.45) 0%, rgba(7, 10, 15, 0.75) 60%, rgba(7, 10, 15, 0.95) 100%),
                        url('https://upload.wikimedia.org/wikipedia/commons/e/eb/Tradisi_Mekotek_04.jpg') center/cover no-repeat;
        }

        .text-gold-gradient {
            background: linear-gradient(180deg, #FFE885 0%, #F3B431 50%, #C48208 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .gold-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(243, 180, 49, 0.7) 50%, transparent 100%);
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(243, 180, 49, 0.25);
        }

        /* Custom smooth modal transitions */
        .modal-blur {
            backdrop-filter: blur(12px);
        }
    </style>
</head>
<body class="bg-darkbg text-slate-100 font-sans antialiased h-full overflow-hidden selection:bg-baligold selection:text-slate-950">

    <main class="relative w-full h-screen hero-bg flex flex-col items-center justify-between p-6 sm:p-10 select-none">
        
        <!-- Top Small Logo Header -->
        <header class="w-full max-w-7xl flex items-center justify-between z-10 pt-2">
            <div class="flex items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gradient-to-br from-baligold via-amber-400 to-amber-600 flex items-center justify-center text-slate-950 font-serif font-black text-xl shadow-xl border-2 border-baligoldlight">
                        M
                    </div>
                    <div class="text-left">
                        <span class="block font-serif text-xs font-bold tracking-widest text-white uppercase">Desa Wisata</span>
                        <span class="block font-serif text-[10px] font-semibold tracking-wider text-baligold uppercase">Munggu</span>
                    </div>
                </div>
            </div>

            <button onclick="openExplorerModal('kontak')" class="text-xs uppercase tracking-widest text-slate-300 hover:text-baligold transition-colors flex items-center gap-2 bg-slate-950/60 border border-slate-800 px-4 py-2 rounded-full backdrop-blur-md">
                <i class="fa-solid fa-phone text-baligold text-xs"></i>
                <span>Kontak Desa Wisata Munggu</span>
            </button>
        </header>

        <div class="max-w-4xl w-full mx-auto text-center z-10 my-auto py-6">
            
            <!-- Main Title Header -->
            <div class="space-y-1 sm:space-y-2">
                <h1 class="font-serif text-3xl sm:text-6xl md:text-7xl font-extrabold text-white tracking-[0.2em] uppercase leading-tight drop-shadow-2xl">
                    DESA WISATA
                </h1>
                <h2 class="font-serif text-5xl sm:text-8xl md:text-9xl font-black text-gold-gradient tracking-wider uppercase leading-none drop-shadow-2xl">
                    MUNGGU
                </h2>
            </div>

            <!-- Subtitle -->
            <p class="font-serif text-baligoldlight text-base sm:text-2xl tracking-[0.25em] uppercase font-light my-5 sm:my-7 drop-shadow">
                Harmony, Tradition & Serenity
            </p>

            <!-- Gold Divider Line -->
            <div class="gold-divider max-w-lg mx-auto my-6 sm:my-8"></div>

            <!-- Description Paragraph -->
            <p class="text-sm sm:text-lg md:text-xl text-slate-200 font-normal tracking-wide max-w-2xl mx-auto leading-relaxed px-4 drop-shadow-md">
                Menemukan kehangatan tradisi, budaya, dan kehidupan masyarakat Bali di Desa Munggu.
            </p>

            <!-- Development Notice -->
            <div class="mt-8 sm:mt-10 inline-flex items-center gap-2.5 bg-slate-950/80 border border-baligold/40 text-baligoldlight px-6 py-3.5 rounded-full backdrop-blur-md shadow-2xl text-xs sm:text-sm font-semibold tracking-wider uppercase">
                <i class="fa-solid fa-compass-drafting text-baligold animate-pulse"></i>
                <span>Website masih dalam tahap pengembangan</span>
            </div>

        </div>

        <!-- Footer Tag -->
        <footer class="w-full text-center z-10 pb-2">
            <p class="text-[11px] text-slate-400 tracking-wider uppercase font-medium">
                <i class="fa-solid fa-location-dot text-baligold mr-1.5"></i> Desa Munggu, Kecamatan Mengwi, Kabupaten Badung, Bali
            </p>
        </footer>

    </main>

    <!-- Modal Kontak Desa Wisata Munggu -->
    <div id="explorer-modal" class="fixed inset-0 z-50 hidden modal-blur bg-slate-950/90 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
        
        <div class="relative w-full max-w-3xl glass-panel rounded-3xl p-6 sm:p-10 text-slate-100 my-auto shadow-2xl border border-baligold/30 max-h-[90vh] overflow-y-auto">
            
            <!-- Close Button -->
            <button onclick="closeExplorerModal()" class="absolute top-5 right-5 w-10 h-10 rounded-full bg-slate-800/80 text-slate-300 hover:text-white hover:bg-balired flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <!-- Header Modal Kontak -->
            <div class="border-b border-slate-800 pb-5 mb-8">
                <h3 class="font-serif text-2xl sm:text-3xl font-bold text-white mb-2">Kontak <span class="text-gold-gradient">Desa Wisata Munggu</span></h3>
                <p class="text-xs sm:text-sm text-slate-400">Informasi resmi kunjungan wisata, kegiatan budaya, dan pemanduan lokal.</p>
            </div>

            <!-- Detail Informasi Kontak -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Alamat -->
                <div class="bg-slate-900/90 p-5 rounded-2xl border border-slate-800 space-y-3">
                    <div class="flex items-center gap-3 text-baligold">
                        <i class="fa-solid fa-location-dot text-lg"></i>
                        <span class="font-bold text-xs uppercase tracking-wider text-white">Alamat Kantor Desa Wisata Munggu</span>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Jalan Nakula Br Pempatan Desa Munggu, Kecamatan Mengwi, Kabupaten Badung, Bali.
                    </p>
                </div>

                <!-- WhatsApp -->
                <div class="bg-slate-900/90 p-5 rounded-2xl border border-slate-800 space-y-3">
                    <div class="flex items-center gap-3 text-emerald-400">
                        <i class="fa-brands fa-whatsapp text-lg"></i>
                        <span class="font-bold text-xs uppercase tracking-wider text-white">WhatsApp Pengelola Desa Wisata Munggu</span>
                    </div>
                    <div>
                        <a href="https://wa.me/6282355136822" target="_blank" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider transition-colors shadow">
                            <i class="fa-brands fa-whatsapp text-sm"></i> 0823-5513-6822
                        </a>
                    </div>
                </div>

                <!-- Instagram -->
                <div class="bg-slate-900/90 p-5 rounded-2xl border border-slate-800 space-y-3">
                    <div class="flex items-center gap-3 text-pink-500">
                        <i class="fa-brands fa-instagram text-lg"></i>
                        <span class="font-bold text-xs uppercase tracking-wider text-white">Instagram</span>
                    </div>
                    <div>
                        <a href="https://instagram.com/desawisata_munggu" target="_blank" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider transition-colors shadow">
                            <i class="fa-brands fa-instagram text-sm"></i> @desawisata_munggu
                        </a>
                    </div>
                </div>

                <!-- TikTok -->
                <div class="bg-slate-900/90 p-5 rounded-2xl border border-slate-800 space-y-3">
                    <div class="flex items-center gap-3 text-cyan-400">
                        <i class="fa-brands fa-tiktok text-lg"></i>
                        <span class="font-bold text-xs uppercase tracking-wider text-white">TikTok</span>
                    </div>
                    <div>
                        <a href="https://tiktok.com/@desawisatamunggu" target="_blank" class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs uppercase tracking-wider transition-colors border border-slate-700 shadow">
                            <i class="fa-brands fa-tiktok text-sm text-cyan-400"></i> desawisatamunggu
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Modal Control
        function openExplorerModal() {
            const modal = document.getElementById('explorer-modal');
            modal.classList.remove('hidden');
        }

        function closeExplorerModal() {
            const modal = document.getElementById('explorer-modal');
            modal.classList.add('hidden');
        }

        // Close on backdrop click
        document.getElementById('explorer-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeExplorerModal();
            }
        });
    </script>
</body>
</html>