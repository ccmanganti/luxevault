@php($item = $getRecord())
<div class="lv-item-summary">
    <img class="lv-item-thumb" src="{{ $item->cover_photo_url }}" alt="{{ $item->name }}" loading="lazy" onerror="this.onerror=null;this.src=@js($item->default_photo_url)">
    <div class="lv-item-info">
        <strong>{{ $item->name }}</strong>
        <p class="lv-item-path" title="{{ $item->warehouse->name }} / {{ $item->inventoryGroup?->project?->name }} / {{ $item->inventoryGroup?->name }}">{{ $item->warehouse->name }}@if($item->inventoryGroup) · {{ $item->inventoryGroup->name }}@endif</p>
        <div class="lv-item-metrics"><b>${{ number_format($item->unit_price,2) }} <small>USD</small></b><span>Qty {{ $item->quantity }}</span></div>
        <div class="lv-item-tags"><span>{{ ucfirst($item->status) }}</span><span>{{ $item->category }}</span></div>
    </div>
</div>
