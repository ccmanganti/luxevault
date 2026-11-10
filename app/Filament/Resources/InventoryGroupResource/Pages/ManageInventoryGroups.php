<?php

namespace App\Filament\Resources\InventoryGroupResource\Pages;

use App\Filament\Resources\InventoryGroupResource;
use Filament\Resources\Pages\ManageRecords;

class ManageInventoryGroups extends ManageRecords
{
    protected static string $resource = InventoryGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [InventoryGroupResource::createAction()];
    }
}
