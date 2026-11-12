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
    use \App\Filament\Concerns\InteractsWithItemActions;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'Scan & act';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.quick-add';

    public ?array $data = [];

    #[\Livewire\Attributes\Locked] public ?int $matchedItemId = null;
    #[\Livewire\Attributes\Locked] public array $matchIds = [];
    #[\Livewire\Attributes\Locked] public bool $showCreate = false;
    #[\Livewire\Attributes\Locked] public bool $lookedUp = false;
    public string $lookupCode = '';

    protected function currentItem(): Item { return $this->cachedRecord($this->matchedItemId ?? 0); }
    public function selectMatch(int $id): void { abort_unless(in_array($id,$this->matchIds,true),404); $this->operations()->record(auth()->user(),$id); $this->matchedItemId=$id;$this->dispatch('vault-scan-result'); }
    public function beginCreate(): void { abort_unless(static::canAccess(),403); $this->showCreate=true;$this->dispatch('vault-scan-stop'); $this->form->fill(['barcode'=>$this->lookupCode,'quantity'=>1,'unit_cost'=>0,'unit_price'=>0,'category'=>'Furniture','condition'=>'good','status'=>'available','low_stock_threshold'=>0]); }
    public function cancelCreate(): void { $this->showCreate=false; }
    public function resetScan(): void { $this->matchedItemId=null;$this->matchIds=[];$this->showCreate=false;$this->lookedUp=false;$this->lookupCode='';$this->operationItem=null;$this->resetErrorBag();$this->dispatch('vault-scan-reset'); }
    protected function getViewData(): array { return ['matched'=>$this->matchedItemId?$this->currentItem():null, 'matches'=>Item::visibleTo(auth()->user())->whereIn('id',$this->matchIds)->with('warehouse')->get()]; }


    public function mount(): void
    {
        $this->form->fill(['quantity' => 1, 'unit_cost' => 0, 'unit_price' => 0, 'category' => 'Furniture', 'condition' => 'good', 'status' => 'available', 'low_stock_threshold' => 0]);
    }

    public function getHeading(): string
    {
        return 'Scan an item';
    }

    public function getSubheading(): string { return 'Use the camera or type a code. Your next action is one tap away.'; }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('inventory.manage') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Wizard::make([
                Step::make('Describe')->description('Name & value')->schema([TextInput::make('barcode')->label('Barcode / SKU scanned')->maxLength(120)->columnSpanFull(), \App\Support\InventoryPhotoField::make(), TextInput::make('name')->label('Item name')->required()->maxLength(255), \App\Support\AccountCategories::field(), TextInput::make('quantity')->integer()->minValue(0)->required(), TextInput::make('unit_cost')->prefix(config('luxe.currency'))->numeric()->minValue(0)->required(), TextInput::make('unit_price')->label('Unit value / price')->prefix(config('luxe.currency'))->numeric()->minValue(0)->required(), Select::make('condition')->options(['good' => 'Good', 'fair' => 'Fair', 'damaged' => 'Damaged'])->required()])->columns(2),
                Step::make('Place')->description('Warehouse & group')->schema([Select::make('warehouse_id')->label('Warehouse')->options(fn () => Warehouse::visibleTo(auth()->user())->pluck('name', 'id'))->required()->live()->afterStateUpdated(fn (Set $set) => $set('inventory_group_id', null)), Select::make('inventory_group_id')->label('Project / group (optional)')->options(fn (Get $get) => InventoryGroup::visibleTo(auth()->user())->whereHas('project', fn ($q) => $q->where('warehouse_id', $get('warehouse_id')))->with('project')->get()->mapWithKeys(fn ($g) => [$g->id => $g->project->name.' / '.$g->name]))->searchable(), TextInput::make('location')->placeholder('Aisle A · Shelf 02'), Textarea::make('notes')->rows(2)]),
            ])->submitAction(new HtmlString('<button type="submit" class="lv-button">Save to my vault →</button>')),
        ]);
    }

    public function scanLookup(?string $code = null): void
    {
        abort_unless(static::canAccess(),403);
        $code = trim($code ?? $this->lookupCode);
        $items = app(\App\Services\ScanLookup::class)->find(auth()->user(),$code);
        $this->lookupCode=$code;
        $this->matchIds=$items->modelKeys();
        $this->matchedItemId=$items->count()===1?$items->first()->id:null;
        $this->showCreate=false; $this->lookedUp=$code!=='';
        $this->dispatch('vault-scan-stop');
        if ($this->lookedUp) $this->dispatch('vault-scan-result');
    }

    public function save(): void
    {
        abort_unless(static::canAccess() && $this->showCreate,403);
        $data = $this->form->getState();
        $data += ['status' => 'available', 'low_stock_threshold' => 0];
        $item = app(InventoryWriter::class)->save(auth()->user(), Item::class, $data);
        Notification::make()->title('Added to your vault')->body($item->name.' · '.$item->sku)->success()->send();
        $this->redirect('/admin/items');
    }
}
