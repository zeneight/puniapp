<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desa Wisata Munggu - Harmoni Tradisi & Pesisir Bali</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Poppins (Modern) & Playfair Display (Elegant Culture) -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome for Cultural & Travel Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Style Config & Animations -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    },
                    colors: {
                        baligold: '#D4AF37',
                        balired: '#8B0000',
                        baliterra: '#C85A17',
                        balidark: '#121212',
                        oceanblue: '#0E7490',
                        oceandeep: '#155E75',
                    }
                }
            }
        }
    </script>
    <style>
        .parallax-bg {
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        /* Glassmorphism utility */
        .glass-nav {
            background: rgba(18, 18, 18, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #121212;
        }
        ::-webkit-scrollbar-thumb {
            background: #C85A17;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #D4AF37;
        }
        
        /* Interactive map styles */
        .map-zone {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        .map-zone:hover {
            filter: drop-shadow(0px 0px 8px currentColor);
            opacity: 0.95;
        }
    </style>
</head>
<body class="bg-[#1C1917] text-stone-100 font-sans overflow-x-hidden">

    <!-- NAVIGATION BAR -->
    <nav class="fixed top-0 left-0 w-full z-50 glass-nav transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-baliterra to-baligold flex items-center justify-center text-white shadow-lg">
                        <i class="fa-solid fa-gopuran text-lg"></i>
                    </div>
                    <div>
                        <span class="font-serif text-xl font-bold tracking-wider text-baligold block leading-tight">MUNGGU</span>
                        <span class="text-xs text-stone-400 uppercase tracking-widest block">Desa Wisata Bali</span>
                    </div>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#beranda" class="text-stone-300 hover:text-baligold transition-colors duration-200 text-sm font-medium tracking-wide">Beranda</a>
                    <a href="#tentang" class="text-stone-300 hover:text-baligold transition-colors duration-200 text-sm font-medium tracking-wide">Tentang</a>
                    <a href="#mekotek" class="text-stone-300 hover:text-baligold transition-colors duration-200 text-sm font-medium tracking-wide">Mekotek</a>
                    <a href="#pantai" class="text-stone-300 hover:text-baligold transition-colors duration-200 text-sm font-medium tracking-wide">Pantai Munggu</a>
                    <a href="#itinerary" class="text-stone-300 hover:text-baligold transition-colors duration-200 text-sm font-medium tracking-wide">Rencanakan Trip</a>
                    <a href="#panduan" class="text-stone-300 hover:text-baligold transition-colors duration-200 text-sm font-medium tracking-wide">Panduan</a>
                </div>

                <!-- Ambient Audio Button & Mobile Toggle -->
                <div class="flex items-center gap-4">
                    <button id="audioBtn" onclick="toggleGamelanAmbient()" class="flex items-center gap-2 px-3 py-1.5 bg-stone-800 hover:bg-baliterra border border-stone-700 hover:border-baligold rounded-full text-xs transition-all duration-300 text-stone-300 hover:text-white" title="Nyalakan Musik Latar Gamelan Bali">
                        <i class="fa-solid fa-music animate-pulse"></i>
                        <span id="audioBtnText">Gamelan Ambient</span>
                    </button>
                    
                    <button id="mobileMenuBtn" class="md:hidden text-stone-300 hover:text-white focus:outline-none">
                        <i class="fa-solid fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu (Hidden by default) -->
        <div id="mobileMenu" class="hidden md:hidden bg-stone-900 border-t border-stone-800 px-4 pt-2 pb-6 space-y-3">
            <a href="#beranda" class="block text-stone-300 hover:text-baligold py-2 font-medium" onclick="toggleMobileMenu()">Beranda</a>
            <a href="#tentang" class="block text-stone-300 hover:text-baligold py-2 font-medium" onclick="toggleMobileMenu()">Tentang Desa</a>
            <a href="#mekotek" class="block text-stone-300 hover:text-baligold py-2 font-medium" onclick="toggleMobileMenu()">Tradisi Mekotek</a>
            <a href="#pantai" class="block text-stone-300 hover:text-baligold py-2 font-medium" onclick="toggleMobileMenu()">Pantai Munggu</a>
            <a href="#itinerary" class="block text-stone-300 hover:text-baligold py-2 font-medium" onclick="toggleMobileMenu()">Rencanakan Trip</a>
            <a href="#panduan" class="block text-stone-300 hover:text-baligold py-2 font-medium" onclick="toggleMobileMenu()">Panduan Wisata</a>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section id="beranda" class="relative h-screen flex items-center justify-center overflow-hidden">
        <!-- Overlay Background dengan Gambar Estetik Desa Bali / Sunset -->
        <div class="absolute inset-0 z-0">
            <img src="https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=1920&q=80" alt="Beautiful Bali Temple Sunset Background" class="w-full h-full object-cover brightness-[0.35]">
            <div class="absolute inset-0 bg-gradient-to-t from-[#1C1917] via-transparent to-black/50"></div>
        </div>

        <div class="relative z-10 text-center px-4 max-w-5xl mx-auto mt-20">
            <span class="inline-block bg-baliterra/30 border border-baligold/50 text-baligold text-xs font-semibold tracking-widest uppercase px-4 py-1.5 rounded-full mb-6">
                <i class="fa-solid fa-map-pin mr-2"></i>Badung, Mengwi, Bali
            </span>
            <h1 class="font-serif text-5xl md:text-7xl lg:text-8xl font-bold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-stone-100 via-stone-200 to-baligold leading-tight mb-6">
                Desa Wisata Munggu
            </h1>
            <p class="text-stone-300 text-lg md:text-xl max-w-2xl mx-auto font-light leading-relaxed mb-10">
                Penyatuan agung antara tradisi sakral tolak bala <span class="text-baligold font-semibold">Mekotek</span> dan ketenangan deburan ombak <span class="text-cyan-400 font-semibold">Pantai Munggu</span> yang eksotis.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="#mekotek" class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-baliterra to-balired hover:from-baligold hover:to-baliterra text-white font-semibold rounded-lg shadow-xl hover:shadow-baliterra/20 transition-all duration-300 transform hover:-translate-y-1 text-center">
                    <i class="fa-solid fa-shield-halved mr-2"></i>Jelajahi Tradisi Mekotek
                </a>
                <a href="#pantai" class="w-full sm:w-auto px-8 py-4 bg-stone-800/80 hover:bg-stone-700/80 border border-stone-600 hover:border-cyan-500 text-stone-100 hover:text-cyan-300 font-semibold rounded-lg transition-all duration-300 transform hover:-translate-y-1 text-center">
                    <i class="fa-solid fa-umbrella-beach mr-2"></i>Pesona Pantai Munggu
                </a>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 text-center animate-bounce">
            <span class="text-xs text-stone-500 uppercase tracking-widest block mb-2">Gulir Kebawah</span>
            <i class="fa-solid fa-angle-down text-baligold text-lg"></i>
        </div>
    </section>

    <!-- ABOUT SECTION (TENTANG DESA) -->
    <section id="tentang" class="py-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto relative">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
            
            <!-- Left Info Panel -->
            <div class="lg:col-span-5 space-y-6">
                <span class="text-baligold font-semibold tracking-widest text-sm uppercase flex items-center gap-2">
                    <!-- Balinese Frangipani (Bunga Jepun) SVG Icon -->
                    <svg viewBox="0 0 24 24" class="w-5 h-5 text-baligold animate-spin-slow" fill="currentColor">
                        <path d="M12 2C11.5 4 9.5 4.5 8 5C6.5 5.5 5 4.5 4.5 6C4 7.5 5 9 4.5 10.5C4 12 2 12.5 2 14C2 15.5 3.5 16 4.5 17.5C5.5 19 4.5 20.5 6 21C7.5 21.5 9 20.5 10.5 21C12 21.5 12.5 23.5 14 23.5C15.5 23.5 16 22 17.5 21C19 20 20.5 21 21 19.5C21.5 18 20.5 16.5 21 15C21.5 13.5 23.5 13 23.5 11.5C23.5 10 22 9.5 21 8C20 6.5 21 5 19.5 4.5C18 4 16.5 5 15 4.5C13.5 4 13 2 12 2Z" opacity="0.8"/>
                        <circle cx="12" cy="12" r="3" fill="#D4AF37"/>
                    </svg>
                    Tentang Desa Wisata
                </span>
                <h2 class="font-serif text-3xl md:text-5xl font-bold text-stone-100 leading-tight">
                    Harmoni Budaya di Jantung Kecamatan Mengwi
                </h2>
                <p class="text-stone-400 leading-relaxed">
                    Desa Wisata Munggu adalah surga tersembunyi yang menjaga erat keaslian adat istiadat Bali di tengah modernisasi pariwisata. Memiliki luas wilayah agraris berpadu pesisir pantai, desa ini berkomitmen penuh pada pariwisata ramah lingkungan berbasis komunitas.
                </p>
                <p class="text-stone-400 leading-relaxed">
                    Dengan memegang teguh asas <strong>Sapta Pesona</strong> (Aman, Tertib, Bersih, Sejuk, Indah, Ramah, Kenangan), Munggu mengundang petualang budaya dan pencari ketenangan laut untuk menyatu bersama kearifan lokal.
                </p>
                
                <!-- Quick Statistics -->
                <div class="grid grid-cols-3 gap-6 pt-6 border-t border-stone-800">
                    <div>
                        <span class="text-3xl font-bold text-baligold font-serif block">1</span>
                        <span class="text-xs text-stone-500 uppercase tracking-wider block mt-1">Warisan Budaya Dunia</span>
                    </div>
                    <div>
                        <span class="text-3xl font-bold text-cyan-400 font-serif block">1.2km</span>
                        <span class="text-xs text-stone-500 uppercase tracking-wider block mt-1">Garis Pantai Hitam Eksotis</span>
                    </div>
                    <div>
                        <span class="text-3xl font-bold text-emerald-400 font-serif block">100%</span>
                        <span class="text-xs text-stone-500 uppercase tracking-wider block mt-1">Hospitalitas Lokal Asli</span>
                    </div>
                </div>
            </div>

            <!-- Right Visual Panel (Interactive Sapta Pesona Cards & Illustrative Map) -->
            <div class="lg:col-span-7 grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- COLUMN 1: Interactive Map Illustration -->
                <div class="bg-stone-900/40 p-6 rounded-2xl border border-stone-800 hover:border-baligold transition-all duration-300 flex flex-col justify-between">
                    <div>
                        <span class="text-baligold font-semibold tracking-widest text-[10px] uppercase block mb-1">
                            <i class="fa-solid fa-map-location-dot mr-1"></i> Peta Ilustrasi Zonasi
                        </span>
                        <h3 class="text-base font-semibold text-stone-200 mb-2">Zonasi Geografis Munggu</h3>
                        <p class="text-[11px] text-stone-500 leading-relaxed mb-4">Ketuk zona wilayah pada peta interaktif di bawah untuk menjelajahi keistimewaan masing-masing area secara visual.</p>
                    </div>

                    <!-- Map SVG Illustration -->
                    <div class="flex items-center justify-center py-2 bg-stone-950/40 rounded-xl border border-stone-800/60 p-2">
                        <svg viewBox="0 0 300 240" class="w-full max-w-[260px] h-auto drop-shadow-lg">
                            <!-- Background base map -->
                            <rect width="300" height="240" rx="16" fill="#171513" stroke="#2E2824" stroke-width="1"/>
                            
                            <!-- Area 1: Subak Persawahan (Green Area) -->
                            <path id="zone-subak" d="M15,15 L285,15 L285,85 L15,85 Z" class="map-zone fill-emerald-950/30 stroke-emerald-500/80 stroke-2 hover:fill-emerald-900/40" onclick="showMapDetail('subak')"/>
                            <text x="150" y="45" fill="#34D399" font-family="Poppins" font-size="11" font-weight="bold" text-anchor="middle" class="pointer-events-none tracking-wider">ZONA SUBAK HIJAU (UTARA)</text>
                            <text x="150" y="62" fill="#A7F3D0" font-family="Poppins" font-size="8" text-anchor="middle" class="pointer-events-none">Ekowisata & Irigasi Sawah Abadi</text>
                            
                            <!-- Area 2: Adat & Pemukiman (Amber Area) -->
                            <path id="zone-adat" d="M15,85 L285,85 L285,155 L15,155 Z" class="map-zone fill-amber-950/30 stroke-amber-500/80 stroke-2 hover:fill-amber-900/40" onclick="showMapDetail('adat')"/>
                            <text x="150" y="115" fill="#FBBF24" font-family="Poppins" font-size="11" font-weight="bold" text-anchor="middle" class="pointer-events-none tracking-wider">ZONA ADAT & MEKOTEK (TENGAH)</text>
                            <text x="150" y="132" fill="#FDE68A" font-family="Poppins" font-size="8" text-anchor="middle" class="pointer-events-none">Pura Kerajaan & Pusat Tradisi Budaya</text>
                            
                            <!-- Area 3: Pantai & Nelayan (Cyan Area) -->
                            <path id="zone-pantai" d="M15,155 L285,155 L285,225 L15,225 Z" class="map-zone fill-cyan-950/30 stroke-cyan-500/80 stroke-2 hover:fill-cyan-900/40" onclick="showMapDetail('pantai')"/>
                            <text x="150" y="185" fill="#22D3EE" font-family="Poppins" font-size="11" font-weight="bold" text-anchor="middle" class="pointer-events-none tracking-wider">ZONA PANTAI & NELAYAN (SELATAN)</text>
                            <text x="150" y="202" fill="#CFFAFE" font-family="Poppins" font-size="8" text-anchor="middle" class="pointer-events-none">Surfing, Kuliner & Pasir Hitam Eksotis</text>
                        </svg>
                    </div>

                    <!-- Dynamic map info box -->
                    <div id="mapInfoBox" class="mt-4 p-3 bg-stone-950 border border-stone-800 rounded-xl text-[11px] text-stone-400 min-h-[64px] flex items-center justify-center text-center transition-all duration-300">
                        <span><i class="fa-solid fa-circle-info text-baligold mr-1"></i> Ketuk salah satu area zona pada peta ilustrasi di atas untuk menyingkap kearifan lokal kawasannya.</span>
                    </div>
                </div>

                <!-- COLUMN 2: Features Cards -->
                <div class="space-y-6">
                    <div class="bg-stone-900/60 p-6 rounded-2xl border border-stone-800 hover:border-baliterra transition-all duration-300">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-baliterra/20 rounded-xl flex items-center justify-center text-baliterra text-lg shrink-0">
                                <i class="fa-solid fa-hands-praying"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-stone-200 mb-1">Kelestarian Adat Sakral</h3>
                                <p class="text-xs text-stone-500 leading-relaxed">Menjaga ritual tolak bala warisan leluhur secara utuh agar tak tergerus gempuran modernitas pariwisata.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-stone-900/60 p-6 rounded-2xl border border-stone-800 hover:border-cyan-500 transition-all duration-300">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-cyan-900/20 rounded-xl flex items-center justify-center text-cyan-400 text-lg shrink-0">
                                <i class="fa-solid fa-leaf"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-stone-200 mb-1">Eko-Wisata Hijau Mandiri</h3>
                                <p class="text-xs text-stone-500 leading-relaxed">Mengembangkan subak persawahan alami serta mengawal pesisir pantai murni tanpa sampah plastik.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-stone-900/60 p-6 rounded-2xl border border-stone-800 hover:border-emerald-500 transition-all duration-300">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-emerald-950/20 rounded-xl flex items-center justify-center text-emerald-400 text-lg shrink-0">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-stone-200 mb-1">Sinergi Komunitas & UMKM</h3>
                                <p class="text-xs text-stone-500 leading-relaxed">Mendorong ekonomi mandiri melalui pemandu adat setempat, homestay warga, dan warung lokal pesisir.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- ILLUSTRATIVE SECTION DIVIDER (BALINESE PATTERN) -->
    <div class="w-full flex justify-center py-6 opacity-30 select-none">
        <svg viewBox="0 0 100 20" class="w-28 h-8 text-baligold" fill="currentColor">
            <!-- Balinese double wave & floral scroll motif -->
            <path d="M50 10 C45 5, 40 5, 35 10 C30 15, 25 15, 20 10 C15 5, 10 5, 5 10 L5 12 C10 7, 15 7, 20 12 C25 17, 30 17, 35 12 C40 7, 45 7, 50 12 C55 7, 60 7, 65 12 C70 17, 75 17, 80 12 C85 7, 90 7, 95 12 L95 10 C90 5, 85 5, 80 10 C75 15, 70 15, 65 10 C60 5, 55 5, 50 10 Z"/>
        </svg>
    </div>

    <!-- MEKOTEK TRADITION SECTION -->
    <section id="mekotek" class="bg-stone-950/80 py-24 border-y border-stone-900 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-baliterra font-semibold tracking-widest text-sm uppercase block mb-2">Ritual Tolak Bala Sakral</span>
                <h2 class="font-serif text-4xl md:text-6xl font-bold text-stone-100 mb-6">Ngerebeg Mekotek</h2>
                <p class="text-stone-400 leading-relaxed">
                    Digelar setiap Hari Raya Kuningan (10 hari setelah Galungan), tradisi sakral ini mempertemukan ribuan krama (pemuda adat) dengan memanggul tongkat kayu pulet sepanjang 2-3 meter, menyatukannya di udara hingga membentuk formasi kerucut berisik nan megah.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <!-- Countdown & Info Panel -->
                <div class="lg:col-span-5 space-y-8 bg-[#201A15] p-8 rounded-2xl border border-baliterra/20 shadow-xl">
                    <div class="text-center lg:text-left">
                        <h3 class="font-serif text-2xl font-semibold text-baligold mb-2">Kuningan & Mekotek 2026</h3>
                        <p class="text-xs text-stone-400 uppercase tracking-widest">Penghitung Mundur Menuju Prosesi Agung Berikutnya</p>
                    </div>

                    <!-- Dynamic Countdown Clock -->
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="bg-stone-900 p-4 rounded-xl border border-stone-800">
                            <span id="cd-days" class="text-3xl font-bold text-baligold font-serif block">00</span>
                            <span class="text-[10px] text-stone-500 uppercase tracking-wider block mt-1">Hari</span>
                        </div>
                        <div class="bg-stone-900 p-4 rounded-xl border border-stone-800">
                            <span id="cd-hours" class="text-3xl font-bold text-baligold font-serif block">00</span>
                            <span class="text-[10px] text-stone-500 uppercase tracking-wider block mt-1">Jam</span>
                        </div>
                        <div class="bg-stone-900 p-4 rounded-xl border border-stone-800">
                            <span id="cd-mins" class="text-3xl font-bold text-baligold font-serif block">00</span>
                            <span class="text-[10px] text-stone-500 uppercase tracking-wider block mt-1">Menit</span>
                        </div>
                        <div class="bg-stone-900 p-4 rounded-xl border border-stone-800">
                            <span id="cd-secs" class="text-3xl font-bold text-baligold font-serif block">00</span>
                            <span class="text-[10px] text-stone-500 uppercase tracking-wider block mt-1">Detik</span>
                        </div>
                    </div>

                    <div class="space-y-4 text-sm text-stone-300">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-calendar-day text-baliterra mt-1 text-base"></i>
                            <div>
                                <strong class="text-stone-100 block">Waktu Pelaksanaan</strong>
                                Setiap Sabtu Kliwon Kuningan, dimulai tepat dari tengah hari di Pura Dalem Munggu hingga meluas ke area jalan raya desa.
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-shirt text-baliterra mt-1 text-base"></i>
                            <div>
                                <strong class="text-stone-100 block">Aturan Berpakaian (Dress Code)</strong>
                                Pengunjung luar wajib menggunakan pakaian adat ringan madya (kamen/kain bawahan Bali dan selendang/senteng).
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-info text-baliterra mt-1 text-base"></i>
                            <div>
                                <strong class="text-stone-100 block">Asal-Usul Sejarah</strong>
                                Dimulai sejak tahun 1915 dari Kerajaan Mengwi sebagai perayaan kemenangan prajurit dan tolak bala pengusir wabah penyakit.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interactive Mekotek Simulator -->
                <div class="lg:col-span-7 bg-[#141210] p-8 rounded-2xl border border-stone-800 shadow-inner">
                    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-semibold text-stone-100">Simulasi Formasi Mekotek</h3>
                            <p class="text-xs text-stone-500">Klik tombol untuk melihat bagaimana tongkat disatukan</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="startMekotekSim()" class="px-4 py-2 bg-baliterra hover:bg-baligold text-white text-xs font-semibold rounded-lg transition-all duration-300">
                                <i class="fa-solid fa-play mr-1"></i> Mulai Gabungkan Kayu
                            </button>
                            <button onclick="resetMekotekSim()" class="px-4 py-2 bg-stone-800 hover:bg-stone-700 text-stone-300 text-xs font-semibold rounded-lg transition-all duration-300">
                                Reset
                            </button>
                        </div>
                    </div>

                    <!-- Simulator Screen -->
                    <div class="relative w-full h-[320px] bg-stone-900 border border-stone-800 rounded-xl overflow-hidden flex items-end justify-center pb-6">
                        <!-- Temple Gate Silhouette Background -->
                        <div class="absolute inset-x-0 bottom-0 h-48 opacity-10 flex justify-center items-end">
                            <svg class="w-72 h-48" viewBox="0 0 100 100" fill="currentColor">
                                <path d="M10 100 L25 100 L25 40 L35 40 L35 15 L32 15 L32 5 L45 5 L45 0 L55 0 L55 5 L68 5 L68 15 L65 15 L65 40 L75 40 L75 100 L90 100 Z" />
                            </svg>
                        </div>

                        <!-- Gamelan Sound Simulator Note Trigger visualizer -->
                        <div id="gamelanViz" class="absolute inset-x-0 top-4 flex justify-center gap-2 px-6 pointer-events-none opacity-0 transition-opacity duration-300">
                            <span class="px-3 py-1 bg-baligold/20 border border-baligold/50 text-baligold rounded-full text-xs font-serif italic">🎵 Pling, Plong, Ding, Dong... Gamelan menyala! 🎵</span>
                        </div>

                        <!-- Crowds and Spears Container -->
                        <div id="spears-container" class="relative w-full h-full max-w-sm flex items-end justify-center">
                            <!-- Left Group of Spears -->
                            <div id="left-spears" class="absolute bottom-0 left-4 w-12 h-48 flex items-end justify-between transition-all duration-1000 ease-out origin-bottom">
                                <div class="w-1.5 h-44 bg-amber-800/80 rounded shadow transform rotate-[35deg] origin-bottom transition-all duration-1000"></div>
                                <div class="w-1.5 h-40 bg-amber-900/80 rounded shadow transform rotate-[25deg] origin-bottom transition-all duration-1000"></div>
                                <div class="w-1.5 h-46 bg-amber-700/80 rounded shadow transform rotate-[15deg] origin-bottom transition-all duration-1000"></div>
                            </div>
                            <!-- Right Group of Spears -->
                            <div id="right-spears" class="absolute bottom-0 right-4 w-12 h-48 flex items-end justify-between transition-all duration-1000 ease-out origin-bottom">
                                <div class="w-1.5 h-46 bg-amber-700/80 rounded shadow transform -rotate-[15deg] origin-bottom transition-all duration-1000"></div>
                                <div class="w-1.5 h-40 bg-amber-900/80 rounded shadow transform -rotate-[25deg] origin-bottom transition-all duration-1000"></div>
                                <div class="w-1.5 h-44 bg-amber-800/80 rounded shadow transform -rotate-[35deg] origin-bottom transition-all duration-1000"></div>
                            </div>

                            <!-- Spear Peak/Cone indicator -->
                            <div id="cone-peak" class="absolute bottom-[200px] left-1/2 -translate-x-1/2 opacity-0 scale-50 transition-all duration-1000 ease-out z-20 flex flex-col items-center">
                                <div class="w-4 h-4 bg-red-600 rounded-full animate-ping mb-2"></div>
                                <span class="bg-red-600 text-stone-100 text-[10px] font-bold px-2 py-0.5 rounded shadow">PEAK FORMATION!</span>
                            </div>

                            <!-- Animated People Silhouettes at bottom -->
                            <div class="absolute bottom-0 inset-x-0 h-10 flex justify-around items-end opacity-70">
                                <div class="w-4 h-8 bg-stone-700 rounded-t-lg"></div>
                                <div class="w-5 h-9 bg-stone-600 rounded-t-lg"></div>
                                <div class="w-4 h-10 bg-stone-800 rounded-t-lg"></div>
                                <div class="w-5 h-7 bg-stone-700 rounded-t-lg"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Simulator Details -->
                    <div class="mt-4 p-4 bg-stone-900 rounded-lg text-xs text-stone-400 space-y-2">
                        <p><strong class="text-baligold">Bagaimana Cara Kerjanya?</strong> Krama terbagi atas 15 banjar adat di Munggu. Kelompok-kelompok banjar berlari membawa tongkat dari arah berlawanan, bertabrakan secara sengaja, lalu menyatukan pucuk tongkat membentuk struktur piramida.</p>
                        <p><strong class="text-baligold">Keberanian Ekstrem:</strong> Pemuda paling bernyali lalu menaiki kerucut tongkat tersebut sambil meneriakkan yel-yel penyemangat perang tanding dan menari bebas di puncak tumpukan kayu setinggi 5-7 meter!</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- MUNGGU BEACH (PANTAI MUNGGU) SECTION -->
    <section id="pantai" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="flex flex-col lg:flex-row items-start lg:items-end justify-between mb-16 gap-4">
            <div>
                <span class="text-cyan-400 font-semibold tracking-widest text-sm uppercase block mb-2">Pantai Tersembunyi di Badung Barat</span>
                <h2 class="font-serif text-4xl md:text-6xl font-bold text-stone-100 leading-tight">Pantai Munggu: Sunset & Ombak Eksotis</h2>
            </div>
            <p class="text-stone-400 max-w-md leading-relaxed">
                Pantai dengan hamparan pasir hitam yang mengkilat dihantam sinar senja, terkenal akan arusnya yang ramah bagi peselancar profesional dan ketenangannya yang cocok untuk melarikan diri dari keramaian wisata masal.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            
            <!-- Pantai Munggu Highlights & Interactive Activity Selector -->
            <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-6">
                
                <!-- Card 1: Surfing Paradise -->
                <div class="group relative rounded-2xl overflow-hidden bg-stone-900 border border-stone-800 hover:border-cyan-500 transition-all duration-300">
                    <div class="h-48 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1502680390469-be75c86b636f?auto=format&fit=crop&w=600&q=80" alt="Surfer catching waves" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 brightness-75">
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-cyan-400 text-xs font-semibold tracking-wide uppercase">Daya Tarik Utama</span>
                            <span class="bg-cyan-900/40 text-cyan-400 text-[10px] px-2 py-0.5 rounded-full">Kondisi Ombak: Sedang - Tinggi</span>
                        </div>
                        <h3 class="text-lg font-semibold text-stone-100 mb-2">Surfing Kelas Dunia</h3>
                        <p class="text-sm text-stone-500 leading-relaxed">Ombak di Pantai Munggu sangat digemari oleh peselancar lokal maupun mancanegara yang mencari suasana damai tanpa antrean ombak yang padat.</p>
                    </div>
                </div>

                <!-- Card 2: Black Sand Sunsets -->
                <div class="group relative rounded-2xl overflow-hidden bg-stone-900 border border-stone-800 hover:border-amber-500 transition-all duration-300">
                    <div class="h-48 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1519046904884-53103b34b206?auto=format&fit=crop&w=600&q=80" alt="Beautiful golden sunset beach" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 brightness-75">
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-amber-400 text-xs font-semibold tracking-wide uppercase">Pemandangan Alam</span>
                            <span class="bg-amber-950/40 text-amber-400 text-[10px] px-2 py-0.5 rounded-full">Mulai Jam 17.30 WITA</span>
                        </div>
                        <h3 class="text-lg font-semibold text-stone-100 mb-2">Golden Sunset di Pasir Hitam</h3>
                        <p class="text-sm text-stone-500 leading-relaxed">Pantai berpasir hitam yang luas menyerap cahaya matahari senja, menciptakan pantulan cermin air yang menakjubkan bagi penggemar fotografi landscape.</p>
                    </div>
                </div>

                <!-- Card 3: Seafood Kuliner Pesisir -->
                <div class="group relative rounded-2xl overflow-hidden bg-stone-900 border border-stone-800 hover:border-emerald-500 transition-all duration-300">
                    <div class="h-48 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&w=600&q=80" alt="Grilled grilled fish with chili" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 brightness-75">
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-emerald-400 text-xs font-semibold tracking-wide uppercase">Kuliner Lokal</span>
                            <span class="bg-emerald-950/40 text-emerald-400 text-[10px] px-2 py-0.5 rounded-full">Tangkapan Nelayan Segar</span>
                        </div>
                        <h3 class="text-lg font-semibold text-stone-100 mb-2">Kuliner Hasil Laut Autentik</h3>
                        <p class="text-sm text-stone-500 leading-relaxed">Nikmati ikan bakar berlumur bumbu rempah khas Bali (Base Gede) di warung-warung lokal sepanjang pantai dengan harga yang sangat ramah di kantong.</p>
                    </div>
                </div>

                <!-- Card 4: Kite Festival & Local Events -->
                <div class="group relative rounded-2xl overflow-hidden bg-stone-900 border border-stone-800 hover:border-purple-500 transition-all duration-300">
                    <div class="h-48 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1610123598147-f632aa18b275?auto=format&fit=crop&w=600&q=80" alt="Beautiful Balinese kites on sky" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 brightness-75">
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-purple-400 text-xs font-semibold tracking-wide uppercase">Atraksi Musiman</span>
                            <span class="bg-purple-950/40 text-purple-400 text-[10px] px-2 py-0.5 rounded-full">Musim Angin: Juni - Agustus</span>
                        </div>
                        <h3 class="text-lg font-semibold text-stone-100 mb-2">Melayangan (Layang-layang Bali)</h3>
                        <p class="text-sm text-stone-500 leading-relaxed">Setiap musim berangin, langit di atas Pantai Munggu dipenuhi dengan layang-layang raksasa tradisional Bebean dan Jangan khas Bali.</p>
                    </div>
                </div>

            </div>

            <!-- Tide & Activity Recommender widget -->
            <div class="lg:col-span-4 bg-[#111A1F] p-8 rounded-2xl border border-cyan-900/30 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-full bg-cyan-900/40 flex items-center justify-center text-cyan-400">
                            <i class="fa-solid fa-cloud-sun-rain"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-stone-100">Kalkulator Rekomendasi Pantai</h3>
                            <p class="text-xs text-stone-400">Tentukan aktivitas terbaik sesuai kondisi pasang surut</p>
                        </div>
                    </div>

                    <!-- Interactive Slider/Buttons to Toggle Tide state -->
                    <div class="space-y-4 mb-8">
                        <label class="text-xs text-stone-400 block font-semibold uppercase tracking-wider">Pilih Kondisi Pasang Surut Laut:</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button onclick="setTide('high')" id="tideHighBtn" class="py-3 px-4 rounded-xl border font-semibold text-xs text-center transition-all duration-300 bg-cyan-500 text-stone-900 border-cyan-400 shadow-lg shadow-cyan-500/10">
                                <i class="fa-solid fa-water-rise mr-2"></i>Pasang (High Tide)
                            </button>
                            <button onclick="setTide('low')" id="tideLowBtn" class="py-3 px-4 rounded-xl border font-semibold text-xs text-center transition-all duration-300 bg-stone-900 text-stone-400 border-stone-800 hover:border-cyan-500">
                                <i class="fa-solid fa-water-lower mr-2"></i>Surut (Low Tide)
                            </button>
                        </div>
                    </div>

                    <!-- Dynamic Recommendation Box -->
                    <div class="p-6 bg-stone-950/60 rounded-xl border border-stone-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-stone-500">Rekomendasi Utama:</span>
                            <span id="recomTag" class="px-2 py-0.5 bg-cyan-900 text-cyan-400 text-[10px] uppercase font-bold rounded-full">Surfing & Watersports</span>
                        </div>
                        <p id="recomDesc" class="text-sm text-stone-300 leading-relaxed">
                            Kondisi pasang sangat ideal bagi para peselancar untuk menaklukkan gulungan ombak Pantai Munggu. Ombak melengkung tinggi di kedalaman terumbu luar menghasilkan sensasi luncuran yang kencang!
                        </p>
                        <ul id="recomActivities" class="space-y-2 text-xs text-stone-400 pt-2 border-t border-stone-800">
                            <li class="flex items-center gap-2"><i class="fa-solid fa-check text-cyan-400"></i> Surfing pemula hingga expert</li>
                            <li class="flex items-center gap-2"><i class="fa-solid fa-check text-cyan-400"></i> Menyaksikan peselancar di anjungan pantai</li>
                            <li class="flex items-center gap-2"><i class="fa-solid fa-xmark text-red-500"></i> Tidak disarankan untuk sekadar berenang bebas</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-cyan-900/30 text-xs text-stone-500 text-center flex flex-col items-center gap-2">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-amber-500 animate-pulse"></i> Keselamatan Pengunjung adalah Prioritas Utama</span>
                    <span>Selalu waspada terhadap rambu bendera merah di area tertentu pantai.</span>
                </div>
            </div>

        </div>
    </section>

    <!-- INTERACTIVE TRIP PLANNER (ITINERARY GENERATOR) -->
    <section id="itinerary" class="bg-gradient-to-b from-stone-950/30 to-[#1C1917] py-24 border-t border-stone-900 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-baligold font-semibold tracking-widest text-sm uppercase block mb-2">Fitur Perencana Perjalanan</span>
                <h2 class="font-serif text-4xl md:text-5xl font-bold text-stone-100 mb-4">Munggu Trip Planner</h2>
                <p class="text-stone-400 leading-relaxed">Rancang pengalaman liburan Anda secara instan. Pilih aktivitas kegemaran Anda, tentukan jumlah pengunjung, dan buat Rencana Perjalanan (*Itinerary*) kustom pribadi.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                <!-- Customizer Form Panel -->
                <div class="lg:col-span-5 bg-stone-900 p-8 rounded-2xl border border-stone-800 space-y-6">
                    <h3 class="text-lg font-semibold text-stone-200 border-b border-stone-800 pb-4">
                        <i class="fa-solid fa-sliders text-baligold mr-2"></i>Sesuaikan Minat Liburan Anda
                    </h3>
                    
                    <!-- Trip Duration Selection -->
                    <div class="space-y-2">
                        <label class="text-xs text-stone-400 block font-semibold uppercase tracking-wider">Durasi Kunjungan:</label>
                        <select id="tripDuration" class="w-full bg-stone-950 border border-stone-800 rounded-xl px-4 py-3 text-sm text-stone-300 focus:outline-none focus:border-baligold transition-colors">
                            <option value="1">1 Hari Kunjungan Kilat (Day Trip)</option>
                            <option value="2">2 Hari 1 Malam (Weekend Getaway)</option>
                        </select>
                    </div>

                    <!-- Primary Interest Selection -->
                    <div class="space-y-2">
                        <label class="text-xs text-stone-400 block font-semibold uppercase tracking-wider">Fokus Minat Kunjungan:</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button onclick="setInterest('culture')" id="interestCulture" class="py-3 px-4 rounded-xl border text-xs text-center font-semibold transition-all duration-300 bg-baliterra text-white border-baliterra shadow-lg shadow-baliterra/10">
                                <i class="fa-solid fa-gopuran mr-2 text-baligold"></i>Edukasi Budaya
                            </button>
                            <button onclick="setInterest('beach')" id="interestBeach" class="py-3 px-4 rounded-xl border text-xs text-center font-semibold transition-all duration-300 bg-stone-950 text-stone-400 border-stone-800 hover:border-cyan-500">
                                <i class="fa-solid fa-shuttlecock mr-2 text-cyan-400"></i>Pesisir & Pantai
                            </button>
                        </div>
                    </div>

                    <!-- Headcount Selection -->
                    <div class="space-y-2">
                        <label class="text-xs text-stone-400 block font-semibold uppercase tracking-wider">Jumlah Anggota Rombongan:</label>
                        <div class="flex items-center gap-4 bg-stone-950 border border-stone-800 rounded-xl px-4 py-2">
                            <button onclick="updateHeadcount(-1)" class="w-8 h-8 rounded-lg bg-stone-800 hover:bg-stone-700 flex items-center justify-center text-stone-200 transition-colors">-</button>
                            <span id="headcountVal" class="text-sm font-semibold text-stone-200 min-w-8 text-center">2 Orang</span>
                            <button onclick="updateHeadcount(1)" class="w-8 h-8 rounded-lg bg-stone-800 hover:bg-stone-700 flex items-center justify-center text-stone-200 transition-colors">+</button>
                        </div>
                    </div>

                    <!-- Dynamic Cost Estimate -->
                    <div class="p-4 bg-stone-950 border border-stone-800 rounded-xl flex justify-between items-center">
                        <div>
                            <span class="text-xs text-stone-500 block">Estimasi Kas Kas Desa / Tiket:</span>
                            <span class="text-[10px] text-stone-500 italic block">(Sumbangan sukarela & parkir)</span>
                        </div>
                        <div class="text-right">
                            <span id="priceVal" class="text-xl font-bold text-emerald-400 font-serif block">Rp 50.000</span>
                            <span class="text-[10px] text-stone-400">Total estimasi</span>
                        </div>
                    </div>

                    <!-- Action Trigger Button -->
                    <button onclick="generateItinerary()" class="w-full py-4 bg-gradient-to-r from-baligold to-baliterra hover:from-baliterra hover:to-baligold text-stone-900 hover:text-white font-bold rounded-xl shadow-lg hover:shadow-baligold/10 transition-all duration-300 transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-wand-magic-sparkles mr-2"></i> Hasilkan Itinerary Saya!
                    </button>
                </div>

                <!-- Live Output Panel (Interactive Itinerary Display) -->
                <div class="lg:col-span-7 bg-stone-900/60 p-8 rounded-2xl border border-stone-800 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-stone-800 pb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-stone-200">
                                <i class="fa-solid fa-receipt text-cyan-400 mr-2"></i>Rencana Perjalanan Anda
                            </h3>
                            <p class="text-xs text-stone-400">Rencana liburan kustom berdasarkan preferensi pilihan Anda</p>
                        </div>
                        <button onclick="copyItinerary()" class="px-4 py-2 bg-stone-800 hover:bg-stone-700 border border-stone-700 hover:border-stone-600 rounded-xl text-xs text-stone-300 hover:text-white flex items-center gap-2 transition-all duration-300">
                            <i class="fa-solid fa-copy"></i>
                            <span id="copyBtnText">Salin Jadwal</span>
                        </button>
                    </div>

                    <!-- Inner Scrollable Itinerary Flow -->
                    <div id="itineraryContainer" class="space-y-6 min-h-[350px]">
                        <!-- Dynamic content will be injected here via javascript -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- TRAVELER GUIDELINES & SAFETY POLICIES (PANDUAN) -->
    <section id="panduan" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="bg-gradient-to-r from-[#2B1B17] to-stone-900 border border-baliterra/20 p-8 sm:p-12 rounded-3xl relative overflow-hidden shadow-2xl">
            <!-- Balinese Motif Background hint -->
            <div class="absolute right-0 bottom-0 opacity-5 pointer-events-none translate-x-12 translate-y-12">
                <i class="fa-solid fa-gopuran text-[250px] text-baligold"></i>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center relative z-10">
                <div class="lg:col-span-7 space-y-6">
                    <span class="text-baligold font-semibold tracking-widest text-sm uppercase block">Panduan Wisata Lokal</span>
                    <h2 class="font-serif text-3xl md:text-5xl font-bold text-stone-100 leading-tight">Mewujudkan Wisata Munggu yang Berkelanjutan</h2>
                    <p class="text-stone-300 leading-relaxed">
                        Kami sangat menyukai keindahan alam dan eratnya ikatan sosial adat kami. Untuk menjaga kenyamanan bersama krama Desa Munggu, mohon patuhi beberapa instruksi berikut selama berwisata:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 text-sm text-stone-300">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-red-900/30 flex items-center justify-center text-red-400">
                                <i class="fa-solid fa-trash-arrow-up"></i>
                            </div>
                            <span>Bebas Sampah Plastik di Pantai</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-red-900/30 flex items-center justify-center text-red-400">
                                <i class="fa-solid fa-volume-xmark"></i>
                            </div>
                            <span>Jaga Kekhidmatan di Dekat Pura</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-red-900/30 flex items-center justify-center text-red-400">
                                <i class="fa-solid fa-person-dress"></i>
                            </div>
                            <span>Pakaian Sopan saat Ritual Adat</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-red-900/30 flex items-center justify-center text-red-400">
                                <i class="fa-solid fa-square-parking"></i>
                            </div>
                            <span>Parkir Tertib di Kantong Resmi</span>
                        </div>
                    </div>
                </div>

                <!-- Driver Online Desa Wisata Booking Simulator -->
                <div class="lg:col-span-5 bg-stone-950 p-6 sm:p-8 rounded-2xl border border-stone-800">
                    <h3 class="text-lg font-semibold text-stone-100 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-taxi text-amber-500"></i>Driver Online Desa Munggu
                    </h3>
                    <p class="text-xs text-stone-400 mb-6 leading-relaxed">
                        Butuh transportasi tepercaya untuk menjangkau spot rahasia di sekitar desa? Hubungi armada lokal resmi bentukan warga Desa Adat Munggu. Ramah, terjangkau, dan langsung terhubung dengan pemandu lokal.
                    </p>
                    
                    <form onsubmit="submitBooking(event)" class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <input type="text" id="bookingName" placeholder="Nama Anda" class="bg-stone-900 border border-stone-800 rounded-lg px-3 py-2 text-xs text-stone-300 focus:outline-none focus:border-baligold w-full" required>
                            <input type="text" id="bookingPhone" placeholder="Nomor WhatsApp" class="bg-stone-900 border border-stone-800 rounded-lg px-3 py-2 text-xs text-stone-300 focus:outline-none focus:border-baligold w-full" required>
                        </div>
                        <select id="bookingRoute" class="w-full bg-stone-900 border border-stone-800 rounded-lg px-3 py-2 text-xs text-stone-300 focus:outline-none focus:border-baligold" required>
                            <option value="airport">Bandara Ngurah Rai ↔ Munggu</option>
                            <option value="tour">Tour Keliling Desa Munggu (Setengah Hari)</option>
                            <option value="kuta">Kuta/Canggu ↔ Pantai Munggu</option>
                        </select>
                        <button type="submit" class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-stone-900 font-bold rounded-lg text-xs transition-colors tracking-wider uppercase">
                            <i class="fa-brands fa-whatsapp mr-1"></i> Pesan Driver Sekarang
                        </button>
                    </form>
                    
                    <div id="bookingResult" class="mt-3 text-center text-xs text-emerald-400 hidden animate-pulse">
                        <i class="fa-solid fa-circle-check mr-1"></i> Permintaan dikirim ke koordinator Armada Lokal!
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-stone-950 border-t border-stone-900 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            
            <!-- Branding/Info -->
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-baliterra to-baligold flex items-center justify-center text-white">
                        <i class="fa-solid fa-gopuran text-lg"></i>
                    </div>
                    <div>
                        <span class="font-serif text-xl font-bold tracking-wider text-baligold block leading-tight">MUNGGU</span>
                        <span class="text-xs text-stone-500 uppercase tracking-widest block">Desa Wisata Bali</span>
                    </div>
                </div>
                <p class="text-xs text-stone-500 leading-relaxed">
                    Pelopor destinasi terpadu berbasis keunikan tradisi luhur dan keindahan pesisir pantai di Kabupaten Badung, Bali.
                </p>
            </div>

            <!-- Quick Links -->
            <div class="space-y-4">
                <h4 class="text-sm font-semibold text-stone-200 tracking-wider uppercase">Navigasi Utama</h4>
                <ul class="space-y-2 text-xs text-stone-400">
                    <li><a href="#beranda" class="hover:text-baligold transition-colors">Beranda</a></li>
                    <li><a href="#tentang" class="hover:text-baligold transition-colors">Tentang Desa Wisata</a></li>
                    <li><a href="#mekotek" class="hover:text-baligold transition-colors">Ngerebeg Mekotek</a></li>
                    <li><a href="#pantai" class="hover:text-baligold transition-colors">Pantai Munggu</a></li>
                </ul>
            </div>

            <!-- Adat & Admin Info -->
            <div class="space-y-4">
                <h4 class="text-sm font-semibold text-stone-200 tracking-wider uppercase">Kontak & Sekretariat</h4>
                <p class="text-xs text-stone-500 leading-relaxed">
                    Kantor Perbekel Desa Munggu<br>
                    Kecamatan Mengwi, Kabupaten Badung, Bali - 80351
                </p>
                <p class="text-xs text-stone-400">
                    <i class="fa-solid fa-phone mr-2 text-baligold"></i>+62 361 xxxx xxx<br>
                    <i class="fa-solid fa-envelope mr-2 text-baligold"></i>info@desawisatamunggu.id
                </p>
            </div>

            <!-- Social Media & Badges -->
            <div class="space-y-4">
                <h4 class="text-sm font-semibold text-stone-200 tracking-wider uppercase">Media Sosial Desa</h4>
                <div class="flex gap-3">
                    <a href="#" class="w-8 h-8 rounded-full bg-stone-900 hover:bg-baliterra border border-stone-800 flex items-center justify-center text-stone-400 hover:text-white transition-colors"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="w-8 h-8 rounded-full bg-stone-900 hover:bg-baliterra border border-stone-800 flex items-center justify-center text-stone-400 hover:text-white transition-colors"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="w-8 h-8 rounded-full bg-stone-900 hover:bg-baliterra border border-stone-800 flex items-center justify-center text-stone-400 hover:text-white transition-colors"><i class="fa-brands fa-youtube"></i></a>
                    <a href="#" class="w-8 h-8 rounded-full bg-stone-900 hover:bg-baliterra border border-stone-800 flex items-center justify-center text-stone-400 hover:text-white transition-colors"><i class="fa-brands fa-tiktok"></i></a>
                </div>
                <div class="pt-2">
                    <span class="inline-flex items-center gap-1 bg-emerald-950 text-emerald-400 text-[10px] font-bold px-3 py-1 rounded-full border border-emerald-900">
                        <i class="fa-solid fa-shield"></i> Sapta Pesona Standard
                    </span>
                </div>
            </div>

        </div>

        <!-- Copyright Disclaimer -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 border-t border-stone-900 pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-stone-600 gap-4">
            <p>&copy; 2026 Desa Wisata Munggu Badung. Semua hak cipta dilindungi undang-undang.</p>
            <p>Dirancang khusus dengan melestarikan tradisi Bali.</p>
        </div>
    </footer>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        // DOM Elements
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        
        // Mobile menu toggle
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        function toggleMobileMenu() {
            mobileMenu.classList.add('hidden');
        }

        // --- WEB AUDIO API BALINESE GAMELAN SIMULATOR ---
        let audioCtx = null;
        let ambientPlaying = false;
        let ambientInterval = null;

        // Custom Pentatonic scale of Bali Gamelan (Selendro / Pelog-like frequencies)
        const gamelanScale = [261.63, 293.66, 329.63, 392.00, 440.00, 523.25]; // Hz frequencies

        function toggleGamelanAmbient() {
            const btnText = document.getElementById('audioBtnText');
            
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }

            if (ambientPlaying) {
                // Stop Gamelan loop
                clearInterval(ambientInterval);
                ambientPlaying = false;
                btnText.textContent = "Gamelan Ambient";
                btnText.parentElement.classList.remove('bg-baliterra', 'border-baligold', 'text-white');
            } else {
                // Resume audio context if suspended
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }
                // Start a simple procedurally generated gamelan metallophone pattern
                ambientPlaying = true;
                btnText.textContent = "🔊 Memutar Gamelan";
                btnText.parentElement.classList.add('bg-baliterra', 'border-baligold', 'text-white');
                playAmbientLoop();
            }
        }

        function playGamelanNote(freq, type = 'sine', duration = 0.6) {
            if (!audioCtx) return;
            
            const osc = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            
            osc.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            
            osc.type = type;
            // Introduce a metallic rich ring using slight frequency modulation or triangle shape
            osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
            
            // Fast attack, slow natural decay (metallic ring)
            gainNode.gain.setValueAtTime(0.2, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
            
            osc.start();
            osc.stop(audioCtx.currentTime + duration);
        }

        function playAmbientLoop() {
            // Generate periodic notes mimicking gangsa/kendang hits
            ambientInterval = setInterval(() => {
                if (!ambientPlaying) return;
                
                // Pick a random frequency from our Balinese Pelog/Selendro approximation scale
                const randomFreq = gamelanScale[Math.floor(Math.random() * gamelanScale.length)];
                playGamelanNote(randomFreq, 'sine', 0.8);
                
                // Occasional high note chime
                if (Math.random() > 0.6) {
                    setTimeout(() => {
                        const chimeFreq = gamelanScale[Math.floor(Math.random() * gamelanScale.length)] * 2;
                        playGamelanNote(chimeFreq, 'triangle', 0.5);
                    }, 150);
                }
            }, 400);
        }

        // --- COUNTDOWN RITUAL MEKOTEK ---
        // Mekotek happens on Hari Raya Kuningan. Next Kuningan is on Saturday, October 31, 2026.
        const targetDate = new Date("Oct 31, 2026 12:00:00").getTime();

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = targetDate - now;

            // Calculations
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const mins = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const secs = Math.floor((distance % (1000 * 60)) / 1000);

            // Output to elements
            document.getElementById("cd-days").innerText = String(days).padStart(2, '0');
            document.getElementById("cd-hours").innerText = String(hours).padStart(2, '0');
            document.getElementById("cd-mins").innerText = String(mins).padStart(2, '0');
            document.getElementById("cd-secs").innerText = String(secs).padStart(2, '0');

            // If countdown is finished, restart to next loop
            if (distance < 0) {
                // Approximate another 210 days later for next Kuningan
                document.getElementById("cd-days").innerText = "---";
            }
        }
        setInterval(updateCountdown, 1000);
        updateCountdown();

        // --- MEKOTEK SIMULATOR ---
        function startMekotekSim() {
            const leftSpears = document.getElementById('left-spears');
            const rightSpears = document.getElementById('right-spears');
            const conePeak = document.getElementById('cone-peak');
            const gamelanViz = document.getElementById('gamelanViz');

            // Transform spears to meet at center
            leftSpears.classList.remove('left-4');
            leftSpears.classList.add('left-[35%]', 'scale-y-110');
            rightSpears.classList.remove('right-4');
            rightSpears.classList.add('right-[35%]', 'scale-y-110');

            // Trigger visual climax indicator
            setTimeout(() => {
                conePeak.classList.remove('opacity-0', 'scale-50');
                conePeak.classList.add('opacity-100', 'scale-100');
                
                // Play procedural sound climax if audio context is active
                if (audioCtx) {
                    playGamelanNote(523.25, 'triangle', 1.0); // high ring
                    playGamelanNote(392.00, 'triangle', 0.8);
                }
                
                // Show game chime trigger status
                gamelanViz.classList.remove('opacity-0');
                setTimeout(() => { gamelanViz.classList.add('opacity-0'); }, 2000);

            }, 800);
        }

        function resetMekotekSim() {
            const leftSpears = document.getElementById('left-spears');
            const rightSpears = document.getElementById('right-spears');
            const conePeak = document.getElementById('cone-peak');

            leftSpears.classList.remove('left-[35%]', 'scale-y-110');
            leftSpears.classList.add('left-4');
            rightSpears.classList.remove('right-[35%]', 'scale-y-110');
            rightSpears.classList.add('right-4');

            conePeak.classList.remove('opacity-100', 'scale-100');
            conePeak.classList.add('opacity-0', 'scale-50');
        }

        // --- TIDE AND WEATHER RECOMMENDER ---
        function setTide(status) {
            const highBtn = document.getElementById('tideHighBtn');
            const lowBtn = document.getElementById('tideLowBtn');
            const recomTag = document.getElementById('recomTag');
            const recomDesc = document.getElementById('recomDesc');
            const recomActivities = document.getElementById('recomActivities');

            if (status === 'high') {
                // UI styles
                highBtn.className = "py-3 px-4 rounded-xl border font-semibold text-xs text-center transition-all duration-300 bg-cyan-500 text-stone-900 border-cyan-400 shadow-lg shadow-cyan-500/10";
                lowBtn.className = "py-3 px-4 rounded-xl border font-semibold text-xs text-center transition-all duration-300 bg-stone-900 text-stone-400 border-stone-800 hover:border-cyan-500";
                
                // Content
                recomTag.innerText = "Surfing & Watersports";
                recomDesc.innerText = "Kondisi air pasang sangat disukai oleh para peselancar untuk menaklukkan deburan ombak luar Pantai Munggu yang kencang. Pasir hitam pantai sebagian tertutup air laut, menyuguhkan pemandangan ombak dramatis.";
                recomActivities.innerHTML = `
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check text-cyan-400"></i> Surfing tingkat menengah dan profesional</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check text-cyan-400"></i> Memotret ombak di anjungan pantai</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-xmark text-red-500"></i> Dilarang berenang bebas atau snorkeling dekat batu karang</li>
                `;
            } else {
                // UI styles
                lowBtn.className = "py-3 px-4 rounded-xl border font-semibold text-xs text-center transition-all duration-300 bg-cyan-500 text-stone-900 border-cyan-400 shadow-lg shadow-cyan-500/10";
                highBtn.className = "py-3 px-4 rounded-xl border font-semibold text-xs text-center transition-all duration-300 bg-stone-900 text-stone-400 border-stone-800 hover:border-cyan-500";
                
                // Content
                recomTag.innerText = "Sunset Stroll & Relax";
                recomDesc.innerText = "Air laut surut menyisakan bibir pantai berpasir hitam yang lebar, rata, dan memantulkan langit keemasan dengan sempurna bagaikan cermin raksasa. Waktu terbaik untuk jalan santai dan berfoto ria.";
                recomActivities.innerHTML = `
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check text-cyan-400"></i> Sunset walking & photography</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check text-cyan-400"></i> Bermain air dangkal di pesisir pasir</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check text-cyan-400"></i> Menikmati seafood bakar segar di warung tepi pantai</li>
                `;
            }
        }

        // --- TRIP PLANNER & ITINERARY GENERATOR ---
        let currentInterest = 'culture';
        let headcount = 2;

        function setInterest(interest) {
            currentInterest = interest;
            const cultBtn = document.getElementById('interestCulture');
            const beachBtn = document.getElementById('interestBeach');

            if (interest === 'culture') {
                cultBtn.className = "py-3 px-4 rounded-xl border text-xs text-center font-semibold transition-all duration-300 bg-baliterra text-white border-baliterra shadow-lg shadow-baliterra/10";
                beachBtn.className = "py-3 px-4 rounded-xl border text-xs text-center font-semibold transition-all duration-300 bg-stone-950 text-stone-400 border-stone-800 hover:border-cyan-500";
            } else {
                beachBtn.className = "py-3 px-4 rounded-xl border text-xs text-center font-semibold transition-all duration-300 bg-cyan-600 text-white border-cyan-600 shadow-lg shadow-cyan-600/10";
                cultBtn.className = "py-3 px-4 rounded-xl border text-xs text-center font-semibold transition-all duration-300 bg-stone-950 text-stone-400 border-stone-800 hover:border-baliterra";
            }
            calculateCost();
        }

        function updateHeadcount(amount) {
            headcount += amount;
            if (headcount < 1) headcount = 1;
            document.getElementById('headcountVal').innerText = `${headcount} Orang`;
            calculateCost();
        }

        function calculateCost() {
            // Standard estimation: 25k per person (parking + donation ticket guide)
            const price = headcount * 25000;
            // Format to Indonesian Rupiah
            const formattedPrice = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(price);
            document.getElementById('priceVal').innerText = formattedPrice;
        }

        function generateItinerary() {
            const duration = document.getElementById('tripDuration').value;
            const container = document.getElementById('itineraryContainer');
            
            let html = '';

            if (currentInterest === 'culture') {
                if (duration === '1') {
                    html = `
                        <div class="space-y-4">
                            <span class="inline-block px-3 py-1 bg-baliterra/20 text-baligold rounded-full text-xs font-semibold">Fokus: 1 Hari Wisata Adat Munggu</span>
                            
                            <div class="relative pl-8 border-l border-stone-800 space-y-6">
                                <div class="relative">
                                    <div class="absolute -left-[41px] top-0 w-6 h-6 rounded-full bg-baliterra flex items-center justify-center text-[10px] text-white font-bold">1</div>
                                    <h4 class="text-sm font-semibold text-stone-200">09.00 WITA - Tiba & Sambutan Hangat</h4>
                                    <p class="text-xs text-stone-400">Berkumpul di Wantilan Pura Dalem Desa Adat Munggu, menikmati jamuan teh sereh dan jajan pasar tradisional Bali bikinan ibu-ibu PKK desa.</p>
                                </div>
                                <div class="relative">
                                    <div class="absolute -left-[41px] top-0 w-6 h-6 rounded-full bg-baliterra flex items-center justify-center text-[10px] text-white font-bold">2</div>
                                    <h4 class="text-sm font-semibold text-stone-200">11.00 WITA - Jelajah Edukasi Mekotek</h4>
                                    <p class="text-xs text-stone-400">Belajar langsung prosesi pembuatan tongkat kayu pulet dari pepohonan liar di hutan kecil desa dan sejarah heroik tolak bala prajurit Mengwi.</p>
                                </div>
                                <div class="relative">
                                    <div class="absolute -left-[41px] top-0 w-6 h-6 rounded-full bg-baliterra flex items-center justify-center text-[10px] text-white font-bold">3</div>
                                    <h4 class="text-sm font-semibold text-stone-200">13.30 WITA - Makan Siang Kuliner Subak</h4>
                                    <p class="text-xs text-stone-400">Makan bersama (Megibung) di gubuk tengah sawah yang dikelola kelompok tani subak Munggu.</p>
                                </div>
                                <div class="relative">
                                    <div class="absolute -left-[41px] top-0 w-6 h-6 rounded-full bg-baliterra flex items-center justify-center text-[10px] text-white font-bold">4</div>
                                    <h4 class="text-sm font-semibold text-stone-200">16.30 WITA - Senja Damai Pantai Munggu</h4>
                                    <p class="text-xs text-stone-400">Penutupan hari dengan bersantai menikmati pemandangan matahari tenggelam di pasir hitam sebelum kembali ke hotel.</p>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    html = `
                        <div class="space-y-6">
                            <span class="inline-block px-3 py-1 bg-baliterra/20 text-baligold rounded-full text-xs font-semibold">Fokus: 2 Hari 1 Malam Kebudayaan Luhur</span>
                            
                            <div class="space-y-4">
                                <h4 class="text-xs text-baligold uppercase font-bold tracking-widest">HARI 1 - Ritual Adat & Sawah Abadi</h4>
                                <div class="relative pl-8 border-l border-stone-800 space-y-4">
                                    <div>
                                        <h5 class="text-xs font-semibold text-stone-200">10.00 WITA - Workshop Ukir & Aksara Bali</h5>
                                        <p class="text-[11px] text-stone-400">Sesi menggambar naskah lontar kuno Bali dan mencoba kostum adat madya.</p>
                                    </div>
                                    <div>
                                        <h5 class="text-xs font-semibold text-stone-200">14.00 WITA - Trekking Pematang Sawah Munggu</h5>
                                        <p class="text-[11px] text-stone-400">Melihat sistem irigasi subak bersejarah dan membantu petani menanam padi lokal.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <h4 class="text-xs text-cyan-400 uppercase font-bold tracking-widest">HARI 2 - Pengalaman Mekotek & Pantai Pasir Hitam</h4>
                                <div class="relative pl-8 border-l border-stone-800 space-y-4">
                                    <div>
                                        <h5 class="text-xs font-semibold text-stone-200">09.00 WITA - Simulasi Atraksi Mekotek</h5>
                                        <p class="text-[11px] text-stone-400">Mengangkat tongkat bersama pemuda adat Munggu, merasakan keseruan menggotong formasi piramida kayu.</p>
                                    </div>
                                    <div>
                                        <h5 class="text-xs font-semibold text-stone-200">15.30 WITA - Berselancar & Seafood Pantai Munggu</h5>
                                        <p class="text-[11px] text-stone-400">Latihan selancar dipandu peselancar profesional lokal desa dilanjutkan santap malam ikan bakar segar.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            } else { // Beach/Coast focused
                if (duration === '1') {
                    html = `
                        <div class="space-y-4">
                            <span class="inline-block px-3 py-1 bg-cyan-900/40 text-cyan-400 rounded-full text-xs font-semibold">Fokus: 1 Hari Penjelajah Pesisir Munggu</span>
                            
                            <div class="relative pl-8 border-l border-stone-800 space-y-6">
                                <div class="relative">
                                    <div class="absolute -left-[41px] top-0 w-6 h-6 rounded-full bg-cyan-600 flex items-center justify-center text-[10px] text-white font-bold">1</div>
                                    <h4 class="text-sm font-semibold text-stone-200">07.30 WITA - Morning Beach Jogging & Yoga</h4>
                                    <p class="text-xs text-stone-400">Menikmati udara murni pagi hari di atas pasir hitam mengkilat dengan ketenangan pesisir yang minim gangguan bising.</p>
                                </div>
                                <div class="relative">
                                    <div class="absolute -left-[41px] top-0 w-6 h-6 rounded-full bg-cyan-600 flex items-center justify-center text-[10px] text-white font-bold">2</div>
                                    <h4 class="text-sm font-semibold text-stone-200">10.00 WITA - Surfing Session Pantai Munggu</h4>
                                    <p class="text-xs text-stone-400">Sewa papan selancar lokal dan mencoba tantangan ombak kanan-kiri yang menantang adrenalin.</p>
                                </div>
                                <div class="relative">
                                    <div class="absolute -left-[41px] top-0 w-6 h-6 rounded-full bg-cyan-600 flex items-center justify-center text-[10px] text-white font-bold">3</div>
                                    <h4 class="text-sm font-semibold text-stone-200">13.00 WITA - Culinary Catch Warung Nelayan</h4>
                                    <p class="text-xs text-stone-400">Sajian ikan laut bakar segar yang baru ditangkap langsung oleh para nelayan lokal Desa Adat Munggu.</p>
                                </div>
                                <div class="relative">
                                    <div class="absolute -left-[41px] top-0 w-6 h-6 rounded-full bg-cyan-600 flex items-center justify-center text-[10px] text-white font-bold">4</div>
                                    <h4 class="text-sm font-semibold text-stone-200">17.00 WITA - Sunset Reflection Hunt</h4>
                                    <p class="text-xs text-stone-400">Sesi foto romantis memanfaatkan efek refleksi pasir hitam basah di bawah lembayung senja Bali yang megah.</p>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    html = `
                        <div class="space-y-6">
                            <span class="inline-block px-3 py-1 bg-cyan-900/40 text-cyan-400 rounded-full text-xs font-semibold">Fokus: 2 Hari 1 Malam Petualangan Pesisir & Kuliner</span>
                            
                            <div class="space-y-4">
                                <h4 class="text-xs text-cyan-400 uppercase font-bold tracking-widest">HARI 1 - Ombak Biru & Sunset Romantis</h4>
                                <div class="relative pl-8 border-l border-stone-800 space-y-4">
                                    <div>
                                        <h5 class="text-xs font-semibold text-stone-200">09.00 WITA - Surf Lesson & Coral Walk</h5>
                                        <p class="text-[11px] text-stone-400">Latihan dasar selancar di pesisir pasir yang aman dan ramah.</p>
                                    </div>
                                    <div>
                                        <h5 class="text-xs font-semibold text-stone-200">16.30 WITA - Sunset Beachside Barbecue</h5>
                                        <p class="text-[11px] text-stone-400">Memanggang udang dan ikan bumbu khas di tepi pantai sambil menanti senja.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <h4 class="text-xs text-baligold uppercase font-bold tracking-widest">HARI 2 - Kearifan Nelayan & Lanskap Sawah</h4>
                                <div class="relative pl-8 border-l border-stone-800 space-y-4">
                                    <div>
                                        <h5 class="text-xs font-semibold text-stone-200">06.00 WITA - Menyaksikan Nelayan Melaut</h5>
                                        <p class="text-[11px] text-stone-400">Interaksi langsung dengan nelayan Munggu, berkesempatan ikut menarik jala pantai.</p>
                                    </div>
                                    <div>
                                        <h5 class="text-xs font-semibold text-stone-200">14.00 WITA - Bersepeda Susur Desa Wisata</h5>
                                        <p class="text-[11px] text-stone-400">Bersepeda dari persawahan subak nan sejuk hingga berakhir di muara sungai Pantai Munggu.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            }

            container.innerHTML = html;
        }

        // Copy itinerary text helper
        function copyItinerary() {
            const container = document.getElementById('itineraryContainer');
            const cleanText = container.innerText;
            const btnText = document.getElementById('copyBtnText');

            // Creating fallback textarea copying
            const el = document.createElement('textarea');
            el.value = "=== DESA WISATA MUNGGU ITINERARY ===\n\n" + cleanText + "\n\nNikmati pesona tradisi Mekotek dan Pantai Munggu!";
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);

            btnText.innerText = "Berhasil Disalin!";
            setTimeout(() => {
                btnText.innerText = "Salin Jadwal";
            }, 2000);
        }

        // --- DRIVER ONLINE BOOKING SUBMIT SIMULATION ---
        function submitBooking(event) {
            event.preventDefault();
            const resultBox = document.getElementById('bookingResult');
            resultBox.classList.remove('hidden');
            setTimeout(() => {
                resultBox.classList.add('hidden');
                document.getElementById('bookingName').value = '';
                document.getElementById('bookingPhone').value = '';
            }, 4000);
        }

        // --- INTERACTIVE MAP DETAIL TRIGGER ---
        function showMapDetail(zone) {
            const infoBox = document.getElementById('mapInfoBox');
            if (zone === 'subak') {
                infoBox.innerHTML = `
                    <div class="text-emerald-400 font-bold mb-1 text-xs"><i class="fa-solid fa-leaf mr-1"></i> Zona Subak Hijau (Utara)</div>
                    <span class="leading-relaxed text-[11px] block text-stone-300">Kawasan hijau dengan sistem pengairan sawah subak tradisional warisan UNESCO. Sangat asri untuk bersepeda pagi, trekking, serta belajar bertani langsung di sawah.</span>
                `;
            } else if (zone === 'adat') {
                infoBox.innerHTML = `
                    <div class="text-amber-400 font-bold mb-1 text-xs"><i class="fa-solid fa-gopuran mr-1"></i> Zona Adat & Mekotek (Tengah)</div>
                    <span class="leading-relaxed text-[11px] block text-stone-300">Pusat peninggalan Kerajaan Mengwi, wantilan krama, sanggar seni kriya ukir kayu, dan episentrum upacara sakral Ngerebeg Mekotek penolak bala.</span>
                `;
            } else if (zone === 'pantai') {
                infoBox.innerHTML = `
                    <div class="text-cyan-400 font-bold mb-1 text-xs"><i class="fa-solid fa-umbrella-beach mr-1"></i> Zona Pesisir Pantai (Selatan)</div>
                    <span class="leading-relaxed text-[11px] block text-stone-300">Bentangan pesisir pasir hitam sepanjang 1.2 KM. Terkenal sebagai surganya para peselancar mancanegara, warung kuliner ikan laut bakar segar, dan refleksi sunset yang syahdu.</span>
                `;
            }
        }

        // Generate default itinerary on load
        window.onload = function() {
            generateItinerary();
        };
    </script>
</body>
</html>