@auth
<nav class="lv-bottom-nav" aria-label="Mobile navigation">
 @foreach([['/admin','heroicon-o-squares-2x2','Overview'],['/admin/items','heroicon-o-cube','Items'],['/admin/quick-add','heroicon-o-qr-code','Scan'],['/admin/projects','heroicon-o-folder','Projects'],['/admin/warehouses','heroicon-o-building-storefront','Vaults']] as [$url,$icon,$label])
 <a href="{{ $url }}" @class(['active'=>request()->path()===ltrim($url,'/')]) @if(request()->path()===ltrim($url,'/')) aria-current="page" @endif>{{ svg($icon) }}<span>{{ $label }}</span></a>
 @endforeach
</nav>
@endauth