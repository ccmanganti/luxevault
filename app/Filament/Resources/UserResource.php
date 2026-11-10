<?php

namespace App\Filament\Resources;

use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountWriter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'People';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 8;

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery()->visibleTo(auth()->user());

        return auth()->user()->isSuper() ? $q : $q->where('owner_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(), TextInput::make('email')->email()->required()->unique(ignoreRecord: true), TextInput::make('password')->password()->revealable()->minLength(12)->required(fn ($record) => ! $record)->helperText('12 characters minimum. Leave blank to keep an existing password.'),
            Select::make('role')->options(['warehouse_owner' => 'Warehouse owner', 'staff' => 'Guest staff', 'superadmin' => 'Superadmin'])->default('staff')->live()->visible(fn () => auth()->user()->isSuper())->disabled(fn ($record) => $record !== null),
            Select::make('owner_id')->label('Guest account owner')->options(fn () => User::role('warehouse_owner')->where('plan', 'premium')->pluck('name', 'id'))->visible(fn (Get $get) => auth()->user()->isSuper() && $get('role') === 'staff')->disabled(fn ($record) => $record !== null)->live()->afterStateUpdated(fn (Set $set) => $set('warehouse_ids', [])),
            Select::make('warehouse_ids')->label('Assigned warehouses')->multiple()->options(fn (Get $get) => Warehouse::where('owner_id', auth()->user()->isSuper() ? $get('owner_id') : auth()->id())->pluck('name', 'id'))->visible(fn (Get $get) => ! auth()->user()->isSuper() || $get('role') === 'staff'),
            Select::make('plan')->options(['free' => 'Free', 'premium' => 'Premium'])->default('free')->visible(fn (Get $get) => auth()->user()->isSuper() && $get('role') !== 'staff'),
            Toggle::make('is_active')->label('Account active')->default(true),
        ])->columns(2);
    }

    public static function createAction(): CreateAction
    {
        return CreateAction::make()->label('Add person')->using(fn (array $data) => app(AccountWriter::class)->save(auth()->user(), $data));
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')->searchable()->weight('bold'), TextColumn::make('email')->searchable(), TextColumn::make('roles.name')->badge(), TextColumn::make('owner.name')->placeholder('Account owner'), TextColumn::make('plan')->badge(), IconColumn::make('is_active')->boolean()])->recordActions([EditAction::make()->mutateRecordDataUsing(fn (array $data, User $record) => array_merge($data, ['role' => $record->getRoleNames()->first(), 'warehouse_ids' => $record->warehouses()->pluck('warehouses.id')->all()]))->using(fn (User $record, array $data) => app(AccountWriter::class)->save(auth()->user(), $data, $record))])->emptyStateHeading('A place for your team')->emptyStateDescription('Premium owners can add guest staff and assign specific warehouses.');
    }

    public static function getPages(): array
    {
        return ['index' => UserResource\Pages\ManageUsers::route('/')];
    }
}
