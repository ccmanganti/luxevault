@php($record = $getRecord())
<div class="lv-record-photo">
    <img src="{{ $record->cover_photo_url }}" alt="{{ $record->name }} — {{ count($record->photos ?? []) ? 'cover photo' : 'default illustration' }}" loading="lazy" onerror="this.onerror=null;this.src=@js($record->default_photo_url)">
    @if(count($record->photos ?? []))
        <span>{{ count($record->photos) }} {{ count($record->photos) === 1 ? 'photo' : 'photos' }}</span>
    @else
        <span>ADD YOUR PHOTO</span>
    @endif
</div>
