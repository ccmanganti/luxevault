<?php

namespace App\Filament\Resources;

use App\Models\InventoryGroup;
use App\Models\Item;
use App\Models\Warehouse;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ItemResource extends BaseResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Item::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Items';

    protected static ?int $navigationSort = 5;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return $schema->components(\App\Support\ItemForm::components());
    }

    public static function table(Table $table): Table
    {
        return \App\Support\ItemTable::configure($table);

    }

    public static function getPages(): array
    {
        return ['index' => ItemResource\Pages\ManageItems::route('/')];
    }
}
