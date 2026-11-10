<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Resources\WarehouseResource;
use Filament\Resources\Pages\ManageRecords;

class ManageWarehouses extends ManageRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        $limited = ! auth()->user()->isSuper() && auth()->user()->plan === 'free' && auth()->user()->ownedWarehouses()->exists();

        return [WarehouseResource::createAction()->disabled($limited)->tooltip($limited ? 'Premium unlocks additional warehouses.' : null)];
    }

    public function getSubheading(): ?string
    {
        return auth()->user()->isOwner() && auth()->user()->plan === 'free'
            ? 'Free includes one warehouse. Premium gives you room for more.'
            : 'Your spaces, locations, and inventory in one place.';
    }
}
