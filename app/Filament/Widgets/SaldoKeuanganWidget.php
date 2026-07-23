<?php

namespace App\Filament\Widgets;

use App\Models\Keuangan; // Sesuaikan dengan nama model database Anda, misal: Transaksi
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SaldoKeuanganWidget extends BaseWidget
{
    // Mengatur widget ini agar tampil di paling bawah (angka terbesar)
    protected static ?int $sort = 99;

    protected function getStats(): array
    {
        // CONTOH 1: Jika database Anda menggunakan kolom 'jenis' (pemasukan/pengeluaran) & 'nominal'
        $totalPemasukan = Keuangan::where('jenis', 'pemasukan')->sum('nominal');
        $totalPengeluaran = Keuangan::where('jenis', 'pengeluaran')->sum('nominal');

        // Rumus Saldo
        $saldoAkhir = $totalPemasukan - $totalPengeluaran;

        return [
            Stat::make('Saldo Akhir', 'Rp ' . number_format($saldoAkhir, 0, ',', '.'))
                ->description('Total kas yang tersedia saat ini')
                ->descriptionIcon('heroicon-m-wallet')
                // Warna dinamis: Hijau jika untung/plus, Merah jika minus
                ->color($saldoAkhir >= 0 ? 'success' : 'danger')
                ->chart([7, 2, 10, 3, 15, 4, 17]) // (Opsional) Membuat grafik gelombang kecil di latar belakang
        ];
    }
}