<?php

namespace App\Filament\Resources;

use App\Services\InventoryWriter;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseResource extends Resource
{
    protected static bool $isDiscovered = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function createAction(): CreateAction
    {
        return CreateAction::make()->using(fn (array $data): Model => app(InventoryWriter::class)->save(auth()->user(), static::getModel(), $data));
    }

    public static function recordActions(): array
    {
        return [EditAction::make()->using(fn (Model $record, array $data): Model => app(InventoryWriter::class)->save(auth()->user(), static::getModel(), $data, $record)), DeleteAction::make()->using(function (Model $record) {
            app(InventoryWriter::class)->delete(auth()->user(), $record);

            return true;
        })];
    }
}
