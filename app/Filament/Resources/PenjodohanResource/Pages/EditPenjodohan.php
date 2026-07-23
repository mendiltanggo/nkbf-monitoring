<?php

namespace App\Filament\Resources\PenjodohanResource\Pages;

use App\Filament\Resources\PenjodohanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenjodohan extends EditRecord
{
    protected static string $resource = PenjodohanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
