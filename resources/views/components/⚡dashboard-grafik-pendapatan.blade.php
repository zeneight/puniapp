<?php

use Livewire\Component;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public function with()
    {
        $user = Auth::user();
        $tahunIni = date('Y');

        // 1. Siapkan Query Dasar
        $query = Transaksi::where('periode_tahun', $tahunIni);

        // Kunci data jika user adalah inputer
        if ($user->role === 'inputer') {
            $query->where('user_id', $user->id);
        }

        // 2. Ambil total nominal di-group berdasarkan bulan (1-12)
        $transaksiBulanan = $query->selectRaw('periode_bulan, SUM(nominal) as total')
                                  ->groupBy('periode_bulan')
                                  ->pluck('total', 'periode_bulan')
                                  ->toArray();

        // 3. Format Array untuk 12 Bulan (Jan - Des) agar data ApexCharts berurutan
        $dataGrafik = [];
        for ($i = 1; $i <= 12; $i++) {
            // Jika bulan tersebut ada datanya, masukkan. Jika tidak, isi 0.
            $dataGrafik[] = $transaksiBulanan[$i] ?? 0;
        }

        return [
            'dataGrafik' => $dataGrafik,
            'tahun' => $tahunIni,
        ];
    }
};
?>

<flux:card>
    <div class="mb-4">
        <flux:heading size="lg">Grafik Pendapatan Punia {{ $tahun }}</flux:heading>
    </div>
    
    <div 
        x-data="{
            init() {
                let options = {
                    series: [{
                        name: 'Total Dana Masuk',
                        data: {{ json_encode($dataGrafik) }}
                    }],
                    chart: {
                        type: 'bar',
                        height: 350,
                        toolbar: { show: false },
                        fontFamily: 'inherit' // Mengikuti font Tailwind bawaan
                    },
                    colors: ['#10b981'], // Warna Emerald Tailwind agar senada dengan icon
                    plotOptions: {
                        bar: { 
                            borderRadius: 4, 
                            horizontal: false, 
                        }
                    },
                    dataLabels: { 
                        enabled: false 
                    },
                    xaxis: {
                        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                    },
                    yaxis: {
                        labels: {
                            formatter: function (val) {
                                return 'Rp ' + val.toLocaleString('id-ID');
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return 'Rp ' + val.toLocaleString('id-ID');
                            }
                        }
                    }
                };
                
                // Render grafik ke dalam div x-ref='chart'
                let chart = new window.ApexCharts(this.$refs.chart, options);
                chart.render();
            }
        }"
    >
        <div x-ref="chart"></div>
    </div>
</flux:card>