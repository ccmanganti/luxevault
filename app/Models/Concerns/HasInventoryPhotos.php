<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

trait HasInventoryPhotos
{
    public function initializeHasInventoryPhotos(): void
    {
        $this->mergeCasts(['photos' => 'array']);
    }

    public function getPhotoKindAttribute(): string
    {
        return match ($this->getTable()) {
            'warehouses' => 'warehouse',
            'projects' => 'project',
            'inventory_groups' => $this->type === 'room' ? 'room' : 'group',
            default => 'item',
        };
    }

    public function getDefaultPhotoUrlAttribute(): string
    {
        return asset('images/defaults/'.$this->photo_kind.'.svg');
    }

    public function getCoverPhotoUrlAttribute(): string
    {
        $photo = $this->photos[0] ?? null;

        return $photo && Storage::disk('local')->exists($photo)
            ? route('inventory.photo', [$this->photo_kind, $this->getKey(), 0])
            : $this->default_photo_url;
    }
}
