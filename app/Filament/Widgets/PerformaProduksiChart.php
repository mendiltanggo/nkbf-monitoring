<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Penjodohan;
use App\Models\Produksi;
use Carbon\Carbon;

class PerformaProduksiChart extends ChartWidget
{
    protected static ?string $heading = 'Performa Produksi per Pasangan';
    protected static ?int $sort = 3; // Menaruh grafik ini di urutan ke-3 (di bawah grafik sebelumnya)
    
    // Membuat grafik ini melebar memenuhi layar (Full Width) agar garisnya terlihat jelas
    protected int | string | array $columnSpan = 'full'; 

    /**
     * FUNGSI INI AKAN OTOMATIS MEMBUAT DROPDOWN DI SUDUT KANAN GRAFIK
     */
    protected function getFilters(): ?array
    {
        // Mengambil semua data penjodohan beserta nama burung dan kandangnya
        $penjodohans = Penjodohan::with(['jantan', 'betina', 'kandang'])->get();
        
        $filters = [];
        foreach ($penjodohans as $p) {
            $jantan = $p->jantan->nama ?? 'Tanpa Nama';
            $betina = $p->betina->nama ?? 'Tanpa Nama';
            $kandang = $p->kandang->kode_kandang ?? '-';
            
            // Format teks di dalam dropdown: "Kdg K-01: Jono x Susi"
            $filters[$p->id] = "Kdg {$kandang}: {$jantan} x {$betina}";
        }
        
        return $filters;
    }

    /**
     * FUNGSI INI MENGAMBIL DATA BERDASARKAN DROPDOWN YANG DIPILIH
     */
    protected function getData(): array
    {
        // $this->filter otomatis berisi ID penjodohan yang sedang dipilih di dropdown
        $activeFilter = $this->filter;

        // Jika user belum memilih apa-apa, pilih pasangan pertama secara otomatis
        if (!$activeFilter) {
            $firstPair = Penjodohan::first();
            $activeFilter = $firstPair ? $firstPair->id : null;
        }

        // Ambil data produksi khusus untuk pasangan yang dipilih, diurutkan dari yang paling lama ke terbaru
        $produksis = Produksi::where('id_penjodohan', $activeFilter)
            ->orderBy('tanggal_bertelur')
            ->get();

        $labels = [];
        $dataTelur = [];
        $dataMenetas = [];
        $dataGagal = [];

        foreach ($produksis as $prod) {
            // Label di bawah grafik: menggunakan tanggal bertelur
            $labels[] = Carbon::parse($prod->tanggal_bertelur)->format('d M Y'); 
            
            $dataTelur[] = $prod->jumlah_telur;
            $dataMenetas[] = $prod->jumlah_menetas;
            $dataGagal[] = $prod->jumlah_gagal;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Bertelur (Kuning)',
                    'data' => $dataTelur,
                    'borderColor' => '#fbbf24', // Warna Garis Kuning
                    'backgroundColor' => 'rgba(251, 191, 36, 0.1)', // Warna bayangan transparan
                    'tension' => 0.3, // Membuat garis sedikit melengkung (tidak kaku)
                    'fill' => true,
                ],
                [
                    'label' => 'Menetas (Hijau)',
                    'data' => $dataMenetas,
                    'borderColor' => '#22c55e', // Warna Garis Hijau
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => 'Gagal / Zonk (Merah)',
                    'data' => $dataGagal,
                    'borderColor' => '#ef4444', // Warna Garis Merah
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Grafik Garis
    }
}