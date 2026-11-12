<?php

namespace App\Filament\Resources;

use App\Models\Project;
use App\Models\Warehouse;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectResource extends BaseResource
{
    protected static bool $isDiscovered = true;

    protected static ?string $model = Project::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Projects';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('warehouse_id')->label('Warehouse')->options(fn () => Warehouse::visibleTo(auth()->user())->pluck('name', 'id'))->required()->searchable(), TextInput::make('name')->required()->maxLength(255), TextInput::make('client_name')->label('Client')->maxLength(255), Textarea::make('notes')->rows(2)])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->contentGrid(['default' => 1, 'md' => 2, '2xl' => 3])->columns([\Filament\Tables\Columns\Layout\Grid::make(['default' => 2])->schema([TextColumn::make('name')->columnSpanFull()->wrap()->searchable()->weight('bold'), TextColumn::make('warehouse.name')->description('Warehouse', position: 'above')->wrap()->badge(), TextColumn::make('client_name')->description('Client', position: 'above')->wrap()->searchable(), TextColumn::make('groups_count')->description('Groups', position: 'above')->wrap()->counts('groups')->label('Groups')])])->recordActions([...static::recordActions()])->defaultSort('created_at', 'desc')->emptyStateHeading('Your projects will appear here')->emptyStateDescription('Add your first record to start organizing your vault.');
    }

    public static function getPages(): array
    {
        return ['index' => ProjectResource\Pages\ManageProjects::route('/')];
    }
}
