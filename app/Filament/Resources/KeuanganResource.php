<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KeuanganResource\Pages;
use App\Filament\Resources\KeuanganResource\RelationManagers;
use App\Models\Keuangan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Forms\Components\Section;

class KeuanganResource extends Resource
{
    protected static ?string $model = Keuangan::class;
    // 1. Mengganti Ikon menjadi gambar uang/dompet
    protected static ?string $navigationIcon = 'heroicon-o-banknotes'; 

    // 2. Mengatur urutan menu (Semakin besar angkanya, semakin di bawah posisinya)
    protected static ?int $navigationSort = 99; 

    // 3. Memperbaiki teks "Keuangans" menjadi bahasa Indonesia yang benar
    protected static ?string $navigationLabel = 'Data Keuangan';
    protected static ?string $pluralModelLabel = 'Data Keuangan';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Membungkus form ke dalam sebuah kotak Section yang elegan
                Section::make('Detail Transaksi Keuangan')
                    ->description('Silakan catat arus kas masuk atau keluar di bawah ini.')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal')
                            ->required()
                            ->default(now()),

                        Forms\Components\TextInput::make('nominal')
                            ->label('Jumlah Uang (Rp)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp') 
                            ->maxValue(1000000000),

                        Forms\Components\Select::make('jenis')
                            ->options([
                                'pemasukan' => 'Pemasukan (Income)',
                                'pengeluaran' => 'Pengeluaran (Expense)',
                            ])
                            ->required()
                            ->live() 
                            ->afterStateUpdated(fn (Set $set) => $set('kategori', null)), 

                        Forms\Components\Select::make('kategori')
                            ->label('Kategori Transaksi')
                            ->options(function (Get $get) {
                                $jenis = $get('jenis');
                                if ($jenis === 'pemasukan') {
                                    return [
                                        'penjualan_burung' => 'Penjualan Burung Trotol/Dewasa',
                                        'penjualan_telur' => 'Penjualan Telur',
                                        'lain_lain' => 'Pemasukan Lain-lain',
                                    ];
                                } elseif ($jenis === 'pengeluaran') {
                                    return [
                                        'pakan_ekstra' => 'Pakan & Ekstra Fooding',
                                        'vitamin_obat' => 'Vitamin & Obat-obatan',
                                        'gaji_karyawan' => 'Gaji Karyawan / Perawat',
                                        'beli_indukan' => 'Beli Burung Indukan Baru',
                                        'peralatan' => 'Peralatan & Perawatan',
                                        'listrik_air' => 'Listrik, Air & Operasional',
                                        'lain_lain' => 'Pengeluaran Lain-lain',
                                    ];
                                }
                                return [];
                            })
                            ->required()
                            ->searchable(),

                        Forms\Components\Textarea::make('keterangan')
                            ->placeholder('Contoh: Beli kroto 1kg dan jangkrik alam')
                            ->columnSpanFull() // Memaksa keterangan untuk selalu mengambil lebar penuh (full width)
                            ->nullable(),
                    ])
                    ->columns([
                        'default' => 1, // KUNCI RESPONSIVE: Di HP (layar kecil), form ditumpuk 1 kolom ke bawah
                        'md' => 2,      // KUNCI RESPONSIVE: Di Tablet/Laptop, form dibariskan 2 kolom ke samping
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis')
                    ->badge()
                    ->color(fn ($state) => $state === 'pemasukan' ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->searchable(),

                Tables\Columns\TextColumn::make('kategori')
                    ->formatStateUsing(function ($state) {
                        // Merapikan teks dari 'pakan_ekstra' menjadi 'Pakan ekstra'
                        return ucfirst(str_replace('_', ' ', $state)); 
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    // Mengubah format angka menjadi format mata uang Rupiah
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color(fn ($record) => $record->jenis === 'pemasukan' ? 'success' : 'danger')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('saldo')
                    ->label('Saldo Akhir')
                    ->getStateUsing(function ($record) {
                        // Hitung semua pemasukan DARI AWAL hingga ke baris transaksi ini
                        $pemasukan = Keuangan::where('id', '<=', $record->id)
                            ->where('jenis', 'pemasukan')
                            ->sum('nominal');

                        // Hitung semua pengeluaran DARI AWAL hingga ke baris transaksi ini
                        $pengeluaran = Keuangan::where('id', '<=', $record->id)
                            ->where('jenis', 'pengeluaran')
                            ->sum('nominal');

                        return $pemasukan - $pengeluaran;
                        })
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color(fn ($state) => $state >= 0 ? 'info' : 'danger') // Saldo positif berwarna biru, minus merah
                    ->weight('bold'),
            ])
            ->defaultSort('tanggal', 'desc') // Mengurutkan dari transaksi terbaru
            ->filters([
                Tables\Filters\Filter::make('rentang_waktu_transaksi')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                // PENTING: Ubah 'tanggal' di bawah ini dengan nama kolom tanggal di database keuangan Anda
                                // (misalnya: 'tanggal_transaksi', 'created_at', atau 'tanggal')
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date), 
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                // Ubah juga yang ini
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Dari: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d M Y'))
                                ->removeField('dari_tanggal');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Sampai: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d M Y'))
                                ->removeField('sampai_tanggal');
                        }
                        return $indicators;
                    })
            ])
            ->headerActions([
                ExportAction::make()->color('success'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()->color('success'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKeuangans::route('/'),
            'create' => Pages\CreateKeuangan::route('/create'),
            'edit' => Pages\EditKeuangan::route('/{record}/edit'),
        ];
    }
}
