<?php

namespace App\Filament\Resources;

use App\Models\InventoryGroup;
use App\Models\Project;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryGroupResource extends BaseResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = InventoryGroup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Rooms & groups';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('project_id')->label('Project')->options(fn () => Project::visibleTo(auth()->user())->with('warehouse')->get()->mapWithKeys(fn ($p) => [$p->id => $p->name.' · '.$p->warehouse->name]))->searchable()->required(), TextInput::make('name')->label('Room or group name')->required()->maxLength(255), Select::make('type')->options(['room' => 'Room', 'custom' => 'Custom collection'])->default('room')->required(), Textarea::make('notes')->rows(2)])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->weight('bold'), TextColumn::make('project.name')->label('Project')->searchable(), TextColumn::make('project.warehouse.name')->label('Warehouse'), TextColumn::make('type')->badge(), TextColumn::make('items_count')->counts('items')])->recordActions([...static::recordActions()])->defaultSort('created_at', 'desc')->emptyStateHeading('Your rooms & groups will appear here')->emptyStateDescription('Add your first record to start organizing your vault.');
    }

    public static function getPages(): array
    {
        return ['index' => InventoryGroupResource\Pages\ManageInventoryGroups::route('/')];
    }
}
