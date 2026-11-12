<nav class="lv-feature-shortcuts" aria-label="Inventory tools">
 @if(\App\Filament\Pages\QuickAdd::canAccess())
 <a href="{{ \App\Filament\Pages\QuickAdd::getUrl() }}"><span class="lv-feature-icon">{{ svg('heroicon-o-qr-code') }}</span><span><strong>Scan & act</strong><small>Find, move or update a piece</small></span>{{ svg('heroicon-o-arrow-up-right', 'lv-feature-arrow') }}</a>
 @endif
 @if(\App\Filament\Pages\Investment::canAccess())
 <a href="{{ \App\Filament\Pages\Investment::getUrl() }}"><span class="lv-feature-icon">{{ svg('heroicon-o-chart-bar') }}</span><span><strong>Investment</strong><small>Costs, usage & decisions</small></span>{{ svg('heroicon-o-arrow-up-right', 'lv-feature-arrow') }}</a>
 @endif
 <a href="{{ \App\Filament\Pages\SharedRecords::getUrl() }}"><span class="lv-feature-icon">{{ svg('heroicon-o-user-group') }}</span><span><strong>Shared records</strong><small>Evidence, people & handoffs</small></span>{{ svg('heroicon-o-arrow-up-right', 'lv-feature-arrow') }}</a>
 @if(\App\Filament\Pages\BrowseInventory::canAccess())
 <a href="{{ \App\Filament\Pages\BrowseInventory::getUrl() }}"><span class="lv-feature-icon">{{ svg('heroicon-o-folder-open') }}</span><span><strong>Browse spaces</strong><small>Warehouse → room → item</small></span>{{ svg('heroicon-o-arrow-up-right', 'lv-feature-arrow') }}</a>
 @endif
</nav>
