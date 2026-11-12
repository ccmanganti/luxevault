@auth
<div x-data="{ moreOpen: false }" @keydown.escape.window="if (moreOpen) { moreOpen = false; $refs.moreButton.focus(); }" class="lv-mobile-navigation">
 <nav class="lv-bottom-nav" aria-label="Mobile navigation">
  @foreach([['/admin','heroicon-o-squares-2x2','Overview'],['/admin/items','heroicon-o-cube','Items'],['/admin/quick-add','heroicon-o-qr-code','Scan'],['/admin/browse-inventory','heroicon-o-folder-open','Browse']] as [$url,$icon,$label])
   <a href="{{ $url }}" @class(['active'=>request()->path()===ltrim($url,'/') || ($label==='Items' && request()->is('admin/item-record'))]) @if(request()->path()===ltrim($url,'/')) aria-current="page" @endif>{{ svg($icon) }}<span>{{ $label }}</span></a>
  @endforeach
  <button type="button" x-ref="moreButton" @click="moreOpen = !moreOpen" :aria-expanded="moreOpen" aria-controls="vault-more-navigation" @class(['active'=>request()->is('admin/investment','admin/shared-records','admin/warehouses*','admin/projects*','admin/inventory-groups*','admin/profile')])>{{ svg('heroicon-o-squares-plus') }}<span>More</span></button>
 </nav>
 <nav id="vault-more-navigation" class="lv-mobile-more" x-show="moreOpen" x-cloak x-transition.opacity.duration.150ms @click.outside="if (!$event.target.closest('.lv-bottom-nav')) moreOpen = false" aria-label="More tools">
  <div class="lv-mobile-more-title"><strong>Your tools</strong><button type="button" @click="moreOpen = false; $refs.moreButton.focus()" aria-label="Close more tools">{{ svg('heroicon-o-x-mark') }}</button></div>
  @if(\App\Filament\Pages\Investment::canAccess())<a href="{{ \App\Filament\Pages\Investment::getUrl() }}">{{ svg('heroicon-o-chart-bar') }}<span>Investment<small>Costs, usage & decisions</small></span></a>@endif
  <a href="{{ \App\Filament\Pages\SharedRecords::getUrl() }}">{{ svg('heroicon-o-user-group') }}<span>Shared records<small>People, evidence & handoffs</small></span></a>
  <a href="/admin/warehouses">{{ svg('heroicon-o-building-storefront') }}<span>Warehouses</span></a><a href="/admin/projects">{{ svg('heroicon-o-folder') }}<span>Projects</span></a><a href="/admin/inventory-groups">{{ svg('heroicon-o-home-modern') }}<span>Rooms & groups</span></a>
  @if($profileUrl=\Filament\Facades\Filament::getProfileUrl())<a href="{{ $profileUrl }}">{{ svg('heroicon-o-cog-6-tooth') }}<span>Settings</span></a>@endif
 </nav>
</div>
@endauth
