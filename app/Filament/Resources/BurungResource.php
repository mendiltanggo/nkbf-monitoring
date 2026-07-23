<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BurungResource\Pages;
use App\Filament\Resources\BurungResource\RelationManagers;
use App\Models\Burung;
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
class BurungResource extends Resource
{
    protected static ?string $modelLabel = 'Data Burung';
    protected static ?string $pluralModelLabel = 'Data Burung';
    protected static ?string $model = Burung::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                section::make('Informasi Dasar Burung')
                ->schema([
                    Forms\Components\TextInput::make('no_ring')
                        ->required()
                        ->maxLength(100),

                    Forms\Components\TextInput::make('nama')
                        ->maxLength(100)
                        ->nullable(),

                    Forms\Components\Select::make('jenis_kelamin')
                        ->label('Jenis Kelamin')
                        ->options([
                            'jantan' => 'Jantan',
                            'betina' => 'Betina',
                        ])
                        ->required(),

                    Forms\Components\Select::make('id_induk_jantan')
                        ->relationship(
                            name: 'indukJantan', 
                            titleAttribute: 'nama',
                            // FILTER: Hanya tampilkan burung Jantan
                            modifyQueryUsing: fn (Builder $query) => $query->where('jenis_kelamin', 'jantan')
                        )
                        ->label('Induk Jantan (Bapak)')
                        ->searchable()
                        ->preload()
                        ->placeholder('Kosongkan jika ini indukan pertama/beli luar')
                        ->nullable(),

                    Forms\Components\Select::make('id_induk_betina')
                        ->relationship(
                            name: 'indukBetina', 
                            titleAttribute: 'nama',
                            // FILTER: Hanya tampilkan burung Betina
                            modifyQueryUsing: fn (Builder $query) => $query->where('jenis_kelamin', 'betina')
                        )
                        ->label('Induk Betina (Ibu)')
                        ->searchable()
                        ->preload()
                        ->placeholder('Kosongkan jika ini indukan pertama/beli luar')
                        ->nullable(),

                    Forms\Components\DatePicker::make('tanggal_menetas')
                        ->nullable(),

                    // Bagian Status Kondisi
                    Forms\Components\Select::make('status_kondisi')
                        ->label('Status / Kondisi Burung')
                        ->options([
                            'trotolan' => 'Trotolan (Anakan)',
                            'siap_produksi' => 'Siap Produksi',
                            'mabung' => 'Sedang Mabung',
                            'sakit' => 'Sakit / Karantina',
                            'terjual' => 'Sudah Terjual',
                            'mati' => 'Mati',
                        ])
                        ->required()
                        ->default('trotolan')
                        ->live() 
                        ->afterStateUpdated(fn (Set $set) => $set('id_kandang', null)), 

                    // Bagian Relasi Kandang Cerdas
                    Forms\Components\Select::make('id_kandang')
                        ->relationship(
                            name: 'kandang', 
                            titleAttribute: 'kode_kandang',
                            modifyQueryUsing: function (Builder $query, ?Burung $record) {
                                $query->where(function ($q) {
                                    // 1. Munculkan kandang yang memang masih 'kosong'
                                    $q->where('status', 'kosong')
                                    // 2. ATAU biarkan kandang anakan/gantung tetap muncul (karena kandang ini boleh diisi banyak burung)
                                    ->orWhere('kategori', '!=', 'ternak');
                                });

                                // 3. PENTING: Saat Edit, pastikan kandang milik burung ini sendiri tetap muncul di pilihan
                                if ($record && $record->id_kandang) {
                                    $query->orWhere('id', $record->id_kandang);
                                }
                            }
                        )
                        ->label('Lokasi Kandang')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        // Lapis Pertahanan ke-2 (Validasi Pencegahan Paksa)
                        ->rule(function (?Burung $record) {
                            return function (string $attribute, $value, \Closure $fail) use ($record) {
                                $kandang = \App\Models\Kandang::find($value);

                                // Jika kandang tidak ketemu, atau burung ini sedang mengedit kandangnya sendiri, loloskan
                                if (!$kandang || ($record && $record->id_kandang == $value)) {
                                    return;
                                }

                                // Jika ada yang memaksa pilih Kandang Ternak yang sudah terisi, TOLAK!
                                if ($kandang->kategori === 'ternak' && $kandang->status === 'terisi') {
                                    $fail('Kandang ternak ini sudah terisi oleh pasangan lain! Pilih kandang yang kosong.');
                                }
                            };
                        }),

                    Forms\Components\Textarea::make('prestasi')
                        ->columnSpanFull()
                        ->nullable(),

                    Forms\Components\Textarea::make('catatan')
                        ->columnSpanFull()
                        ->nullable(),
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
                Tables\Columns\TextColumn::make('no_ring')->label('No. Ring')->searchable()->sortable() ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('nama')->label('Nama Burung')->searchable(),
                Tables\Columns\TextColumn::make('indukJantan.nama')
                    ->label('Silsilah induk jantan')
                    ->default('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('indukBetina.nama')
                    ->label('Silsilah induk betina')
                    ->default('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('jenis_kelamin')->label('Jenis Kelamin')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->searchable() // <-- Fitur Search
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_menetas')->label('Umur')
                    ->getStateUsing(function ($record) {
                        if (!$record->tanggal_menetas) return 'Belum diketahui';
                        $umur = \Carbon\Carbon::parse($record->tanggal_menetas)->diff(\Carbon\Carbon::now());
                        return $umur->y > 0 ? "{$umur->y} Tahun, {$umur->m} Bulan" : "{$umur->m} Bulan, {$umur->d} Hari";
                    })->sortable() ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('kandang.kode_kandang')->label('Lokasi Kandang')->badge()
                    ->getStateUsing(function ($record) {
                        if (!$record->kandang) return 'Tidak di kandang';
                        $kategori = match ($record->kandang->kategori) {
                            'ternak' => 'Ternak', 'anakan' => 'Anakan', 'gantung' => 'Gantung', 'alumunium' => 'Alumunium', default => '-',
                        };
                        return "Kdg {$kategori} ({$record->kandang->kode_kandang})";
                    })
                    ->color(fn ($record) => match ($record->kandang?->kategori) {
                        'ternak' => 'success', 'anakan' => 'warning', 'gantung' => 'info', default => 'gray',
                    })
                    ->searchable(['kode_kandang', 'kategori']), // <-- Bisa mencari no kandang ATAU kategorinya
                Tables\Columns\TextColumn::make('status_kondisi')->label('Status')->badge()
                    ->getStateUsing(function ($record) {
                        $sedangDijodohkan = \App\Models\Penjodohan::where('status', 'aktif')
                            ->where(function ($query) use ($record) {
                                $query->where('id_burung_jantan', $record->id)->orWhere('id_burung_betina', $record->id);
                            })->exists();
                        return $sedangDijodohkan ? 'perjodohan' : $record->status_kondisi;
                    })
                    ->color(fn ($state) => match ($state) {
                        'siap_produksi' => 'success', 'perjodohan' => 'info', 'trotolan' => 'warning', 'mabung' => 'warning', 'sakit' => 'danger', 'terjual' => 'success', 'mati' => 'danger', default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'trotolan' => 'Trotolan (Anakan)', 'siap_produksi' => 'Siap Produksi', 'mabung' => 'Sedang Mabung', 'sakit' => 'Sakit / Karantina', 'terjual' => 'Terjual', 'mati' => 'Mati', 'perjodohan' => 'Sedang Dijodohkan', default => ucfirst($state),
                    })
                    ->searchable(), // <-- Fitur Search Status
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
            'index' => Pages\ListBurungs::route('/'),
            'create' => Pages\CreateBurung::route('/create'),
            'edit' => Pages\EditBurung::route('/{record}/edit'),
        ];
    }
}
