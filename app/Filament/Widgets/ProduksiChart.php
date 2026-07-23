<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Produksi;
use Illuminate\Support\Carbon;

class ProduksiChart extends ChartWidget
{
    protected static ?string $heading = 'Statistik Total Produksi';
    protected static ?int $sort = 2; // Tampil di sebelah/bawah grafik burung

    // Menentukan filter bawaan saat halaman pertama kali dimuat
    public ?string $filter = 'all'; 

    /**
     * MEMBUAT DROPDOWN FILTER
     */
    protected function getFilters(): ?array
    {
        return [
            'week' => '1 Minggu Terakhir',
            'month' => '1 Bulan Terakhir',
            '6_months' => '6 Bulan Terakhir',
            'year' => '1 Tahun Terakhir',
            'all' => 'Semua Waktu',
        ];
    }

    protected function getData(): array
    {
        // 1. Ambil nilai filter yang sedang aktif
        $activeFilter = $this->filter;

        // 2. Siapkan query dasar
        $query = Produksi::query();

        // 3. Modifikasi query berdasarkan filter (kecuali 'all')
        if ($activeFilter && $activeFilter !== 'all') {
            $date = match ($activeFilter) {
                'week' => Carbon::now()->subWeek(),
                'month' => Carbon::now()->subMonth(),
                '6_months' => Carbon::now()->subMonths(6),
                'year' => Carbon::now()->subYear(),
            };

            // Menggunakan 'tanggal_bertelur' sebagai patokan waktu. 
            // (Bisa diganti 'tanggal_panen_piyik' atau 'created_at' jika Anda mau)
            $query->whereDate('tanggal_panen_piyik', '>=', $date);
        }

        // 4. Hitung total berdasarkan query yang sudah difilter
        $totalTelur = $query->sum('jumlah_telur');
        $totalMenetas = $query->sum('jumlah_menetas');
        $totalZonk = $query->sum('jumlah_gagal');

        return [
            'datasets' => [
                [
                    'label' => 'Total (Butir/Ekor)',
                    'data' => [
                        $totalTelur, 
                        $totalMenetas, 
                        $totalZonk
                    ],
                    'backgroundColor' => [
                        '#3b82f6', // Biru (Total Telur)
                        '#22c55e', // Hijau (Menetas)
                        '#ef4444', // Merah (Zonk / Gagal)
                    ],
                    'borderRadius' => 8,
                ],
            ],
            'labels' => ['Total Telur', 'Menetas', 'Zonk / Gagal'],
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Jenis grafik: Batang
    }
}