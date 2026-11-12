<?php

namespace App\Support;

use Filament\Forms\Components\FileUpload;

class InventoryPhotoField
{
    public static function make(): FileUpload
    {
        return FileUpload::make('photos')
            ->label('Photos')
            ->disk('local')
            ->visibility('private')
            ->directory(fn () => 'vault-photos/'.auth()->id())
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->multiple()
            ->maxFiles(6)
            ->maxSize(5120)
            ->reorderable()
            ->appendFiles()
            ->openable()
            ->preventFilePathTampering()
            ->imagePreviewHeight('140')
            ->panelLayout('grid')
            ->placeholder('Add photos or drop them here')
            ->helperText('Up to 6 JPG, PNG or WebP photos, 5 MB each. Drag to reorder; the first photo is the cover. Leave empty to use the default artwork.')
            ->columnSpanFull();
    }
}
