<?php

namespace App\Filament\Resources\BurungResource\Pages;

use App\Filament\Resources\BurungResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBurung extends EditRecord
{
    protected static string $resource = BurungResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
