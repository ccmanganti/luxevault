<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ItemResource;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\InventoryWriter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class InventoryTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Inventory at a glance';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return \App\Support\ItemTable::configure($table)->query(Item::visibleTo(auth()->user()))->paginated([6, 12, 24])->defaultPaginationPageOption(6);
    }
}
