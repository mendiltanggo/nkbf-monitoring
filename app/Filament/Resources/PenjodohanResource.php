<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenjodohanResource\Pages;
use App\Filament\Resources\PenjodohanResource\RelationManagers;
use App\Models\Penjodohan;
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
use Illuminate\Validation\Rules\Unique;

class PenjodohanResource extends Resource
{
    protected static ?string $modelLabel = 'Data Penjodohan';
    protected static ?string $pluralModelLabel = 'Data Penjodohan';
    protected static ?string $model = Penjodohan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                section::make('Informasi Data Perjodohan')
                    ->schema([
                        // Menggunakan Select, bukan TextInput
                        Forms\Components\Select::make('id_kandang')
                            ->relationship(
                                name: 'kandang', 
                                titleAttribute: 'kode_kandang',
                                modifyQueryUsing: function (Builder $query, ?Penjodohan $record) {
                                    
                                    // 1. FILTER ASLI ANDA: Tetap hanya tampilkan yang berkategori 'ternak'
                                    $query->where('kategori', 'ternak'); 

                                    // 2. FILTER TAMBAHAN: Kumpulkan kandang yang sedang dipakai pasangan 'aktif'
                                    $queryKandangTerpakai = Penjodohan::where('status', 'aktif');

                                    // PENTING: Jika Anda sedang mengedit data pasangan, 
                                    // kandang mereka sendiri tidak boleh disembunyikan
                                    if ($record) {
                                        $queryKandangTerpakai->where('id', '!=', $record->id);
                                    }

                                    $kandangTerpakaiIds = $queryKandangTerpakai->pluck('id_kandang')->filter()->toArray();

                                    // 3. Sembunyikan kandang yang sudah terpakai dari daftar
                                    if (!empty($kandangTerpakaiIds)) {
                                        $query->whereNotIn('id', $kandangTerpakaiIds);
                                    }
                                }
                            )
                            ->label('Pilih Kandang Ternak')
                            ->searchable()
                            ->preload()
                            ->required()
                            
                            // LAPISAN KE-2: Keamanan Validasi Database (Mencegah ada yang memaksa lewat inspect element)
                            ->unique(
                                table: 'penjodohan',
                                column: 'id_kandang',
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule) => $rule->where('status', 'aktif')
                            ),

                        Forms\Components\Select::make('id_burung_jantan')
                            ->relationship(
                                name: 'jantan', 
                                titleAttribute: 'nama',
                                // Logika Filter: Hanya panggil Jantan yang Siap Produksi
                                modifyQueryUsing: fn (Builder $query) => $query->where('jenis_kelamin', 'jantan')->where('status_kondisi', 'siap_produksi')
                            )
                            ->label('Burung Jantan')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('id_burung_betina')
                            ->relationship(
                                name: 'betina', 
                                titleAttribute: 'nama',
                                // Logika Filter: Hanya panggil Betina yang Siap Produksi
                                modifyQueryUsing: fn (Builder $query) => $query->where('jenis_kelamin', 'betina')->where('status_kondisi', 'siap_produksi')
                            )
                            ->label('Burung Betina')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->required(),
                            
                        Forms\Components\DatePicker::make('tanggal_selesai'),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'aktif' => 'Aktif',
                                'selesai' => 'Selesai',
                            ])
                            ->default('aktif')
                            ->required(),
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
                Tables\Columns\TextColumn::make('kandang.kode_kandang')->label('Lokasi Kandang')->badge()
                    ->getStateUsing(function ($record) {
                        if (!$record->kandang) return '-';
                        $kategori = match ($record->kandang->kategori) {
                            'ternak' => 'Ternak', 'anakan' => 'Anakan', 'gantung' => 'Gantung', 'alumunium' => 'Alumunium', default => '-',
                        };
                        return "Kdg {$kategori} ({$record->kandang->kode_kandang})";
                    })->color('success')
                    ->searchable(['kode_kandang', 'kategori']), // <-- Search Kandang (Kode & Kategori)

                Tables\Columns\TextColumn::make('jantan.nama')->label('Burung Jantan')
                    ->description(fn ($record) => 'Ring: ' . ($record->jantan->no_ring ?? '-'))
                    ->searchable(['nama', 'no_ring']) // <-- Search Nama & Ring Jantan
                    ->sortable(),

                Tables\Columns\TextColumn::make('betina.nama')->label('Burung Betina')
                    ->description(fn ($record) => 'Ring: ' . ($record->betina->no_ring ?? '-'))
                    ->searchable(['nama', 'no_ring']) // <-- Search Nama & Ring Betina
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_mulai')->label('Lama Perjodohan')
                    ->getStateUsing(function ($record) {
                        $mulai = \Carbon\Carbon::parse($record->tanggal_mulai);
                        $selesai = $record->status === 'selesai' && $record->tanggal_selesai ? \Carbon\Carbon::parse($record->tanggal_selesai) : \Carbon\Carbon::now();
                        $diff = $mulai->diff($selesai);
                        return $diff->y > 0 ? "{$diff->y} Tahun, {$diff->m} Bulan" : ($diff->m > 0 ? "{$diff->m} Bulan, {$diff->d} Hari" : "{$diff->d} Hari");
                    })->description(fn ($record) => 'Mulai: ' . \Carbon\Carbon::parse($record->tanggal_mulai)->format('d M Y'))->sortable(),

                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('Tanggal Selesai')
                    ->alignCenter()
                    ->formatStateUsing(function ($state, $record) {
                        // Jika statusnya 'selesai' dan tanggal_selesai sudah diisi
                        if ($record->status === 'selesai' && $state) {
                            return \Carbon\Carbon::parse($state)->format('d M Y');
                        }
                        
                        // Jika statusnya masih 'aktif' (atau tanggal kosong), tampilkan strip
                        return '-';
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($state) => $state === 'aktif' ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->searchable(), // <-- Search Status Perjodohan
            ])
            ->filters([])
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
            'index' => Pages\ListPenjodohans::route('/'),
            'create' => Pages\CreatePenjodohan::route('/create'),
            'edit' => Pages\EditPenjodohan::route('/{record}/edit'),
        ];
    }
}
