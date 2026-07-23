<?php

namespace App\Filament\Resources\PenjodohanResource\Pages;

use App\Filament\Resources\PenjodohanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenjodohans extends ListRecords
{
    protected static string $resource = PenjodohanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
