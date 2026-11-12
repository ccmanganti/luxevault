<?php

namespace App\Filament\Pages;

use App\Models\InventoryGroup;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\InventoryWriter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class QuickAdd extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'Scan & quick add';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.quick-add';

    public ?array $data = [];

    public ?int $matchedItemId = null;

    public function mount(): void
    {
        $this->form->fill(['quantity' => 1, 'unit_cost' => 0, 'unit_price' => 0, 'category' => 'Furniture', 'condition' => 'good', 'status' => 'available', 'low_stock_threshold' => 0]);
    }

    public function getHeading(): string
    {
        return 'From camera to collection.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('inventory.manage') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Wizard::make([
                Step::make('Identify')->description('Scan or type')->schema([View::make('components.scanner'), TextInput::make('barcode')->label('Barcode or QR content')->maxLength(120)->live(onBlur: true)->afterStateUpdated(fn ($state) => $this->scanLookup((string) $state))->placeholder('Scan or enter a barcode / SKU')->helperText('Scan a manufacturer code, or leave blank to generate your own label.')]),
                Step::make('Describe')->description('Name & value')->schema([\App\Support\InventoryPhotoField::make(), TextInput::make('name')->label('Item name')->required()->maxLength(255), \App\Support\AccountCategories::field(), TextInput::make('quantity')->integer()->minValue(0)->required(), TextInput::make('unit_cost')->prefix(config('luxe.currency'))->numeric()->minValue(0)->required(), TextInput::make('unit_price')->label('Unit value / price')->prefix(config('luxe.currency'))->numeric()->minValue(0)->required(), Select::make('condition')->options(['good' => 'Good', 'fair' => 'Fair', 'damaged' => 'Damaged'])->required()])->columns(2),
                Step::make('Place')->description('Warehouse & group')->schema([Select::make('warehouse_id')->label('Warehouse')->options(fn () => Warehouse::visibleTo(auth()->user())->pluck('name', 'id'))->required()->live()->afterStateUpdated(fn (Set $set) => $set('inventory_group_id', null)), Select::make('inventory_group_id')->label('Project / group (optional)')->options(fn (Get $get) => InventoryGroup::visibleTo(auth()->user())->whereHas('project', fn ($q) => $q->where('warehouse_id', $get('warehouse_id')))->with('project')->get()->mapWithKeys(fn ($g) => [$g->id => $g->project->name.' / '.$g->name]))->searchable(), TextInput::make('location')->placeholder('Aisle A · Shelf 02'), Textarea::make('notes')->rows(2)]),
            ])->submitAction(new HtmlString('<button type="submit" class="lv-button">Save to my vault →</button>')),
        ]);
    }

    public function scanLookup(string $code): void
    {
        $code = mb_substr(trim($code), 0, 120);
        $this->data['barcode'] = $code;
        $this->matchedItemId = null;
        if ($code === '') {
            return;
        }
        $item = Item::visibleTo(auth()->user())->where(fn ($q) => $q->where('barcode', $code)->orWhere('sku', $code)->orWhere('qr_code', $code))->first();
        $this->matchedItemId = $item?->id;
        if ($item) {
            Notification::make()->title('Already in your vault: '.$item->name)->body('Open the existing label or continue to add a separate item.')->info()->send();
        }
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data += ['status' => 'available', 'low_stock_threshold' => 0];
        $item = app(InventoryWriter::class)->save(auth()->user(), Item::class, $data);
        Notification::make()->title('Added to your vault')->body($item->name.' · '.$item->sku)->success()->send();
        $this->redirect('/admin/items');
    }
}
