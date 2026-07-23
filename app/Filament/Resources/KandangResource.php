<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KandangResource\Pages;
use App\Filament\Resources\KandangResource\RelationManagers;
use App\Models\Kandang;
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

class KandangResource extends Resource
{
    protected static ?string $modelLabel = 'Data Kandang';
    protected static ?string $pluralModelLabel = 'Data Kandang';
    protected static ?string $model = Kandang::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                section::make('Informasi Data Kandang')
                    ->schema([
                        Forms\Components\TextInput::make('kode_kandang')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\Select::make('kategori')
                            ->options([
                                'ternak' => 'Kandang Ternak',
                                'anakan' => 'Kandang Anakan',
                                'gantung' => 'Kandang Gantung',
                                'alumunium' => 'Kandang Alumunium',
                            ])
                            ->required()
                            ->default('ternak'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'kosong' => 'Kosong',
                                'terisi' => 'Terisi',
                                'sterilisasi' => 'Sterilisasi',
                            ])
                            ->required()
                            ->default('kosong'),
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
                Tables\Columns\TextColumn::make('kode_kandang')
                    ->label('No. Kandang')
                    ->searchable() // <-- Search No Kandang
                    ->sortable(),
                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->searchable(), // <-- Search Kategori
                Tables\Columns\TextColumn::make('tipe')
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn ($state) => match ($state) {
                        'kosong' => 'success', 'terisi' => 'warning', 'sterilisasi' => 'danger', default => 'gray',
                    })
                    ->searchable(), // <-- Search Status Kandang
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
            'index' => Pages\ListKandangs::route('/'),
            'create' => Pages\CreateKandang::route('/create'),
            'edit' => Pages\EditKandang::route('/{record}/edit'),
        ];
    }
}
