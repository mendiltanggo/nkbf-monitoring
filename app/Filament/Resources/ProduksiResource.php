<?php

namespace App\Filament\Resources;

use Filament\Forms\Set;
use Illuminate\Support\Carbon;
use App\Filament\Resources\ProduksiResource\Pages;
use App\Filament\Resources\ProduksiResource\RelationManagers;
use App\Models\Produksi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Forms\Components\Section;


class ProduksiResource extends Resource
{
    protected static ?string $modelLabel = 'Data Produksi';
    protected static ?string $pluralModelLabel = 'Data Produksi';
    protected static ?string $model = Produksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                section::make('Informasi Data Produksi')
                    ->schema([
                        // Mengubah id_penjodohan menjadi Dropdown
                        Forms\Components\Select::make('id_penjodohan')
                            ->relationship('penjodohan', 'id') // Tetap gunakan ID sebagai dasar relasi
                            ->getOptionLabelFromRecordUsing(function (\App\Models\Penjodohan $record) {
                                // Tarik data relasi
                                $kandang = $record->kandang->kode_kandang ?? 'Tanpa Kandang';
                                $jantan = $record->jantan->nama ?? '?';
                                $betina = $record->betina->nama ?? '?';
                                
                                // Gabungkan menjadi teks yang cantik
                                return "{$kandang} ({$jantan} x {$betina})";
                            })
                            ->label('Lokasi Kandang Ternak')
                            ->required()
                            ->searchable(['kandang.kode_kandang', 'jantan.nama', 'betina.nama'])
                            ->preload(),

                        Forms\Components\DatePicker::make('tanggal_bertelur')
                            ->required()
                            ->live(onBlur: true) 
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    // Menggunakan \Carbon\Carbon agar tidak error jika belum import class
                                    $prediksi = \Carbon\Carbon::parse($state)->addDays(14)->format('Y-m-d');
                                    $set('tanggal_menetas_prediksi', $prediksi);
                                }
                            }),

                        // INI YANG SEBELUMNYA HILANG: Kolom penampung hasil prediksi
                        Forms\Components\DatePicker::make('tanggal_menetas_prediksi')
                            ->label('Prediksi Menetas (+14 Hari)')
                            ->readOnly()
                            ->helperText('Otomatis dihitung 14 hari dari tanggal bertelur.'),

                        Forms\Components\TextInput::make('jumlah_telur')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0), // Mencegah input angka minus

                        Forms\Components\DatePicker::make('tanggal_menetas_aktual')
                            ->label('Tanggal Menetas Aktual')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $set('status', 'menetas'); // Otomatis ubah status ke menetas
                                }
                            }),

                        Forms\Components\TextInput::make('jumlah_menetas')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\TextInput::make('jumlah_gagal')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\DatePicker::make('tanggal_panen_piyik')
                            ->label('Tanggal Panen Piyik')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $set('status', 'panen'); // Otomatis ubah status ke panen
                                }
                            }),

                        // Mengubah TextInput menjadi Select Options
                        Forms\Components\Select::make('status')
                            ->options([
                                'bertelur' => 'Sedang Bertelur',
                                'mengerami' => 'Sedang Mengerami',
                                'menetas' => 'Sudah Menetas',
                                'panen' => 'Selesai Panen',
                                'gagal' => 'Gagal / Zonk',
                            ])
                            ->required()
                            ->default('bertelur'),

                        Forms\Components\Textarea::make('catatan')
                            ->columnSpanFull(),
                        ])
                        ->columns([
                            'default' => 1, // 1 Kolom untuk HP
                            'md' => 2,      // 2 Kolom untuk Laptop/PC
                        ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('penjodohan.kandang.kode_kandang')->label('Kandang')->badge()
                    ->getStateUsing(function ($record) {
                        if (!$record->penjodohan || !$record->penjodohan->kandang) return '-';
                        $kandang = $record->penjodohan->kandang;
                        $kategori = match ($kandang->kategori) { 'ternak' => 'Ternak', 'anakan' => 'Anakan', 'gantung' => 'Gantung', 'alumunium' => 'Alumunium', default => '-' };
                        return "Kdg {$kategori} ({$kandang->kode_kandang})";
                    })->color('success')
                    ->searchable()
                    ->sortable(), // <-- Search Kandang di Produksi

                Tables\Columns\TextColumn::make('penjodohan.jantan.nama')->label('Induk Jantan')
                    ->description(fn ($record) => 'Ring: ' . ($record->penjodohan?->jantan?->no_ring ?? '-'))
                    ->searchable(['nama', 'no_ring']) // <-- Search Jantan
                    ->sortable(),

                Tables\Columns\TextColumn::make('penjodohan.betina.nama')->label('Induk Betina')
                    ->description(fn ($record) => 'Ring: ' . ($record->penjodohan?->betina?->no_ring ?? '-'))
                    ->searchable(['nama', 'no_ring']) // <-- Search Betina
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_bertelur')->label('Tgl Bertelur')->date('d M Y')->sortable(),

                Tables\Columns\TextColumn::make('jumlah_telur')->label('Jml Telur')->alignCenter()
                    ->searchable(), // <-- Search Jumlah Telur
                    
                Tables\Columns\TextColumn::make('jumlah_menetas')->label('Menetas')->alignCenter()->color('success')
                    ->searchable(), // <-- Search Jumlah Menetas
                    
                Tables\Columns\TextColumn::make('jumlah_gagal')->label('Zonk')->alignCenter()->color('danger')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // <-- Search Jumlah Gagal

                Tables\Columns\TextColumn::make('tanggal_panen_piyik')
                    ->label('Tgl Panen')
                    ->alignCenter()
                    ->formatStateUsing(function ($state, $record) {
                        // Jika statusnya 'panen' dan tanggalnya sudah diisi
                        if ($record->status === 'panen' && $state) {
                            return \Carbon\Carbon::parse($state)->format('d M Y');
                        }
                        
                        // Jika statusnya bukan 'panen', berikan tanda strip
                        return '-';
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match ($state) { 'bertelur' => 'info', 'mengerami' => 'warning', 'menetas' => 'success', 'panen' => 'success', 'gagal' => 'danger', default => 'gray' })
                    ->formatStateUsing(fn ($state) => match ($state) { 'bertelur' => 'Sedang Bertelur', 'mengerami' => 'Sedang Mengerami', 'menetas' => 'Sudah Menetas', 'panen' => 'Selesai Panen', 'gagal' => 'Gagal / Zonk', default => ucfirst($state) })
                    ->searchable() // <-- Search Status Produksi
            ])
            ->filters([Tables\Filters\Filter::make('rentang_tanggal_panen')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal Panen'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal Panen'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_panen_piyik', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_panen_piyik', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Panen Dari: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->format('d M Y'))
                                ->removeField('dari_tanggal');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = Tables\Filters\Indicator::make('Panen Sampai: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->format('d M Y'))
                                ->removeField('sampai_tanggal');
                        }
                        return $indicators;
                    })
            ])
            ->headerActions([
                ExportAction::make()
                ->label('Export Semua ke Excel')
                ->color('success'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make()
                    ->label('Export Excel (Terpilih)')
                    ->color('success'),
                ])
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
            'index' => Pages\ListProduksis::route('/'),
            'create' => Pages\CreateProduksi::route('/create'),
            'edit' => Pages\EditProduksi::route('/{record}/edit'),
        ];
    }
}
