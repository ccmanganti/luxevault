<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $limited = ! auth()->user()->isSuper() && auth()->user()->plan === 'free';

        return [UserResource::createAction()->disabled($limited)->tooltip($limited ? 'Guest accounts are available on Premium.' : null)];
    }

    public function getSubheading(): ?string
    {
        return auth()->user()->isOwner() && auth()->user()->plan === 'free'
            ? 'Your Free account is for you. Premium adds guest staff with warehouse-specific access.'
            : 'Manage access and keep everyone in the right spaces.';
    }
}
