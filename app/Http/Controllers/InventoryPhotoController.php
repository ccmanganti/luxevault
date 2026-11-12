<?php

namespace App\Http\Controllers;

use App\Models\InventoryGroup;
use App\Models\Item;
use App\Models\Project;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class InventoryPhotoController
{
    public function __invoke(string $kind, int $record, int $index = 0)
    {
        $class = match ($kind) {
            'warehouse' => Warehouse::class,
            'project' => Project::class,
            'room', 'group' => InventoryGroup::class,
            'item' => Item::class,
            default => abort(404),
        };
        $model = $kind === 'item' ? app(\App\Services\ItemOperations::class)->record(auth()->user(), $record) : $class::visibleTo(auth()->user())->findOrFail($record);
        if ($kind !== 'item') Gate::authorize('view', $model);
        $path = $model->photos[$index] ?? null;
        $disk = Storage::disk('local');
        if (! $path || ! $disk->exists($path)) {
            return redirect($model->default_photo_url);
        }
        abort_unless(in_array($disk->mimeType($path), ['image/jpeg', 'image/png', 'image/webp'], true), 404);

        return response()->file($disk->path($path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
