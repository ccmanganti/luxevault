<x-filament-panels::page>
@include('filament.pages.dashboard-style')
<section class="lv-dashboard-hero">
 <img src="https://static.wixstatic.com/media/a3e817_63000d199c96475a97f37e2c0d132c61~mv2.webp/v1/fill/w_1000%2Ch_563%2Cal_c%2Cq_85%2Cusm_0.66_1.00_0.01/a3e817_63000d199c96475a97f37e2c0d132c61~mv2.webp" alt="Neutral living room with sculptural furniture and layered textures" fetchpriority="high">
 <div class="lv-dashboard-hero-content"><span class="lv-dashboard-kicker">THE ART OF KEEPING THINGS IN PLACE</span><h2>Beautiful spaces.<br>Thoughtfully managed.</h2><p>From the warehouse shelf to the finishing touch.<br>Your collection, ready for its next chapter.</p><div class="lv-dashboard-actions"><a href="/admin/items" class="lv-button">Explore inventory →</a><a href="/admin/projects" class="lv-dashboard-link">View projects ↗</a></div></div>
 <div class="lv-dashboard-hero-caption"><span>THE MATERIAL EDIT</span><p>Warm neutrals · natural textures · considered details</p><a href="https://www.hadainteriors.com/post/cheshire-interior-designer-luxury-home-styling-tips" target="_blank" rel="noopener noreferrer">Interior inspiration · Hada Interiors ↗</a></div>
</section>
<div class="lv-dashboard-section-heading"><div><span class="lv-dashboard-kicker">AT A GLANCE</span><h2>Your collection, in numbers.</h2></div><span class="lv-dashboard-currency">All monetary values in USD</span></div>
<div class="lv-overview">
 <div class="lv-overview-main">
  <div class="lv-stats">
   <a href="/admin/items" class="lv-stat lv-stat-gold"><span>INVENTORY VALUE · USD</span><strong>${{ number_format($totals->value, 2) }}</strong><small>Across {{ number_format($totals->units) }} pieces <b>{{ svg('heroicon-o-arrow-up-right', 'lv-arrow') }}</b></small></a>
   <a href="/admin/warehouses" class="lv-stat"><span>YOUR WAREHOUSES</span><strong>{{ $warehouses->count() }}</strong><small>{{ auth()->user()->isSuper() ? 'All accounts' : ucfirst(auth()->user()->owner?->plan ?? auth()->user()->plan).' workspace' }} <b>{{ svg('heroicon-o-arrow-up-right', 'lv-arrow') }}</b></small></a>
   <a href="/admin/items" class="lv-stat"><span>ACQUISITION COST · USD</span><strong>${{ number_format($totals->cost, 2) }}</strong><small>{{ number_format($totals->records) }} unique item records <b>{{ svg('heroicon-o-arrow-up-right', 'lv-arrow') }}</b></small></a>
   <a href="/admin/items" class="lv-stat"><span>NEEDS A LOOK</span><strong>{{ $lowStock }}</strong><small>Items at or below stock threshold <b>{{ svg('heroicon-o-arrow-up-right', 'lv-arrow') }}</b></small></a>
  </div>
 </div>
 <section class="lv-card"><div class="lv-card-heading"><h2>By collection category</h2><span class="lv-dot"></span></div><p class="lv-muted">A clear picture of what you hold.</p>
 <div class="lv-category-total"><strong>{{ number_format($totals->units) }}</strong><span>total pieces</span></div>
 <div class="lv-bars">@forelse($categories as $category)<div><div class="lv-bar-label"><span>{{ $category->category }}</span><b>{{ number_format($category->units) }}</b></div><div class="lv-track"><i style="width:{{ $totals->units ? max(2,$category->units/$totals->units*100) : 0 }}%"></i></div></div>@empty<p class="lv-muted">Your category breakdown appears when you add items.</p>@endforelse</div>
 </section>
</div>
<div class="lv-detail-grid">
 <section class="lv-card lv-dashboard-spaces"><div class="lv-spaces-image" role="img" aria-label="Warm interior with coordinated furnishings"><span>SPACES WITH PURPOSE</span></div><div class="lv-card-heading"><h2>Your spaces</h2><a href="/admin/warehouses">Manage {{ svg('heroicon-o-arrow-up-right', 'lv-arrow') }}</a></div><p class="lv-muted">{{ $projects }} projects across your warehouses.</p>
 @forelse($warehouses as $warehouse)<div class="lv-space"><span class="lv-space-icon">{{ svg('heroicon-o-building-storefront') }}</span><div><b>{{ $warehouse->name }}</b><small>{{ $warehouse->address ?: 'Add an address to this space' }}</small></div><span class="lv-count">{{ $warehouse->items_count }}</span></div>@empty<div class="lv-empty"><p>Give your inventory a home.</p><a class="lv-button" href="/admin/warehouses">Create a warehouse {{ svg('heroicon-o-arrow-up-right', 'lv-arrow') }}</a></div>@endforelse
 </section>
 <section class="lv-card"><h2>Stock status</h2><p class="lv-muted">Ready, reserved, or out in the world.</p><div class="lv-status-list">@forelse($statuses as $status)<div><span><i class="lv-status-dot {{ $status->status }}"></i>{{ ucfirst($status->status) }}</span><b>{{ number_format($status->units) }}</b></div>@empty<p class="lv-muted">No stock recorded yet.</p>@endforelse</div><a class="lv-text-link" href="/admin/projects">Organize by project →</a></section>
 <section class="lv-card"><h2>Recent movement</h2><p class="lv-muted">The latest changes in your vault.</p><ol class="lv-timeline">@forelse($events as $event)<li><b>{{ $event->item_name }}</b><span>{{ $event->action }} · {{ $event->user?->name ?? 'Former user' }}</span><small>{{ $event->created_at->diffForHumans() }}</small></li>@empty<li><b>A fresh start</b><span>Your inventory activity will appear here.</span></li>@endforelse</ol></section>
</div>
@livewire(\App\Filament\Widgets\InventoryTable::class)
</x-filament-panels::page>