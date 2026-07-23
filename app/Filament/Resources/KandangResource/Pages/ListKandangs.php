<?php

namespace App\Filament\Resources\KandangResource\Pages;

use App\Filament\Resources\KandangResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKandangs extends ListRecords
{
    protected static string $resource = KandangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
