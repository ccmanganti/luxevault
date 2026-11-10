<?php

namespace App\Filament\Pages;

use App\Models\InventoryEvent;
use App\Models\Item;
use App\Models\Project;
use App\Models\Warehouse;
use Filament\Actions\Action;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected string $view = 'filament.pages.dashboard';

    public function getHeading(): string
    {
        return 'Your warehouse, in focus.';
    }

    public function getSubheading(): string
    {
        return 'Every piece. Every space. One clear view.';
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('quickAdd')->label('Scan & add')->icon('heroicon-o-qr-code')->url(QuickAdd::getUrl())];
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $items = Item::visibleTo($user);
        $totals = (clone $items)->selectRaw('COUNT(*) as records, COALESCE(SUM(quantity),0) as units, COALESCE(SUM(quantity * unit_cost),0) as cost, COALESCE(SUM(quantity * unit_price),0) as value')->first();

        return ['totals' => $totals, 'warehouses' => Warehouse::visibleTo($user)->withCount('items')->get(), 'projects' => Project::visibleTo($user)->count(), 'lowStock' => (clone $items)->whereColumn('quantity', '<=', 'low_stock_threshold')->count(), 'categories' => (clone $items)->selectRaw('category, SUM(quantity) as units')->groupBy('category')->orderByDesc('units')->get(), 'statuses' => (clone $items)->selectRaw('status, SUM(quantity) as units')->groupBy('status')->get(), 'events' => InventoryEvent::visibleTo($user)->with('user')->latest('created_at')->limit(6)->get()];
    }
}
