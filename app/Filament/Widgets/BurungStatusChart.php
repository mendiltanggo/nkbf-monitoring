<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Burung;
use App\Models\Penjodohan;

class BurungStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Status Keseluruhan Burung';
    protected static ?int $sort = 1; // Menentukan urutan tampil di dashboard

    protected function getData(): array
    {
        // 1. Cari ID burung jantan & betina yang sedang dalam masa penjodohan aktif
        $burungDijodohkanIds = Penjodohan::where('status', 'aktif')
            ->get()
            ->flatMap(fn ($p) => [$p->id_burung_jantan, $p->id_burung_betina])
            ->filter()
            ->unique()
            ->toArray();
            
        $countPerjodohan = count($burungDijodohkanIds);
        
        // 2. Hitung jumlah burung berdasarkan status aslinya
        $countSiapProduksi = Burung::where('status_kondisi', 'siap_produksi')
            ->whereNotIn('id', $burungDijodohkanIds) // Kurangi dengan yang sedang dijodohkan
            ->count();
            
        $countTrotolan = Burung::where('status_kondisi', 'trotolan')->count();
        $countMabung = Burung::where('status_kondisi', 'mabung')->count();
        $countSakit = Burung::where('status_kondisi', 'sakit')->count();
        $countTerjual = Burung::where('status_kondisi', 'terjual')->count();
        $countMati = Burung::where('status_kondisi', 'mati')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Burung (Ekor)',
                    'data' => [
                        $countTrotolan, 
                        $countSiapProduksi, 
                        $countPerjodohan, 
                        $countMabung, 
                        $countSakit, 
                        $countTerjual, 
                        $countMati
                    ],
                    'backgroundColor' => [
                        '#fbbf24', // Kuning (Trotolan)
                        '#22c55e', // Hijau (Siap Produksi)
                        '#3b82f6', // Biru (Perjodohan)
                        '#f97316', // Oranye (Mabung)
                        '#ef4444', // Merah (Sakit)
                        '#14b8a6', // Tosca (Terjual)
                        '#374151', // Abu Tua (Mati)
                    ],
                ],
            ],
            'labels' => ['Trotolan', 'Siap Produksi', 'Sedang Dijodohkan', 'Mabung', 'Sakit', 'Terjual', 'Mati'],
        ];
    }

    protected function getType(): string
    {
        return 'pie'; // Jenis grafik: Lingkaran
    }
}