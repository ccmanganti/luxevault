<?php
// Isolated checks for hosts without PHPUnit. Never connects to the live database.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (! extension_loaded('pdo_sqlite')) {
    throw new RuntimeException('pdo_sqlite is required for isolated photo checks.');
}
config([
    'database.default' => 'photo_smoke',
    'database.connections.photo_smoke' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
    'cache.default' => 'array',
    'permission.cache.store' => 'array',
    'session.driver' => 'array',
    'mail.default' => 'array',
    'filesystems.disks.local.root' => sys_get_temp_dir().'/vault-photo-smoke-'.bin2hex(random_bytes(8)),
    'filesystems.disks.local.serve' => true,
]);
$app->forgetInstance(Spatie\Permission\PermissionRegistrar::class);
Illuminate\Support\Facades\DB::purge('photo_smoke');
$disk = Illuminate\Support\Facades\Storage::disk('local');
$check = function ($condition, string $message) {
    if (! $condition) {
        throw new RuntimeException($message);
    }
};
try {
    Illuminate\Support\Facades\Artisan::call('migrate', ['--database' => 'photo_smoke', '--force' => true]);
    (new Database\Seeders\RoleSeeder)->run();
    $owner = App\Models\User::create(['name' => 'Photo test owner', 'email' => 'photo-test@example.test', 'password' => 'unused-test-password']);
    $owner->assignRole('warehouse_owner');
    $other = App\Models\User::create(['name' => 'Other test owner', 'email' => 'other-photo-test@example.test', 'password' => 'unused-test-password']);
    $other->assignRole('warehouse_owner');
    Illuminate\Support\Facades\Auth::setUser($owner);
    Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
    $writer = app(App\Services\InventoryWriter::class);
    $photo = 'vault-photos/'.$owner->id.'/sample.png';
    $disk->put($photo, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jfusAAAAASUVORK5CYII='));
    $warehouse = $writer->save($owner, App\Models\Warehouse::class, ['name' => 'Sample warehouse', 'photos' => [$photo]]);
    $project = $writer->save($owner, App\Models\Project::class, ['name' => 'Sample project', 'warehouse_id' => $warehouse->id, 'photos' => [$photo]]);
    $group = $writer->save($owner, App\Models\InventoryGroup::class, ['name' => 'Sample room', 'project_id' => $project->id, 'type' => 'room', 'photos' => [$photo]]);
    $data = ['name' => 'Sample chair', 'warehouse_id' => $warehouse->id, 'inventory_group_id' => $group->id, 'category' => 'Furniture', 'condition' => 'good', 'status' => 'available', 'quantity' => 1, 'low_stock_threshold' => 0, 'unit_cost' => 20, 'unit_price' => 40, 'photos' => [$photo]];
    $item = $writer->save($owner, App\Models\Item::class, $data);
    foreach ([$warehouse, $project, $group, $item] as $model) {
        $check($model->fresh()->photos === [$photo], 'Gallery did not persist.');
        $check(str_contains($model->cover_photo_url, '/inventory-photo/'), 'Photo cover URL missing.');
    }
    $response = app(App\Http\Controllers\InventoryPhotoController::class)('item', $item->id);
    $check($response->getStatusCode() === 200, 'Photo route did not serve the image.');
    Illuminate\Support\Facades\Auth::setUser($other);
    try {
        app(App\Http\Controllers\InventoryPhotoController::class)('item', $item->id);
        throw new RuntimeException('Foreign photo was exposed.');
    } catch (Illuminate\Database\Eloquent\ModelNotFoundException $expected) {
    }
    Illuminate\Support\Facades\Auth::setUser($owner);
    $foreign = 'vault-photos/'.$other->id.'/foreign.png';
    $disk->copy($photo, $foreign);
    foreach ([$foreign, 'vault-photos/'.$owner->id.'/../foreign.png'] as $badPath) {
        try {
            $writer->save($owner, App\Models\Item::class, array_merge($data, ['photos' => [$badPath]]), $item);
            throw new RuntimeException('Invalid photo path accepted.');
        } catch (Illuminate\Validation\ValidationException $expected) {
        }
    }
    $item = $writer->save($owner, App\Models\Item::class, array_merge($data, ['photos' => []]), $item);
    $check(str_contains($item->cover_photo_url, '/images/defaults/item.svg'), 'Missing default after removing photos.');
    $disk->delete($photo);
    $check(str_contains($warehouse->cover_photo_url, '/images/defaults/warehouse.svg'), 'Missing file did not fall back.');
    foreach ([App\Filament\Resources\ItemResource\Pages\ManageItems::class, App\Filament\Resources\WarehouseResource\Pages\ManageWarehouses::class, App\Filament\Resources\ProjectResource\Pages\ManageProjects::class, App\Filament\Resources\InventoryGroupResource\Pages\ManageInventoryGroups::class, App\Filament\Pages\QuickAdd::class] as $page) {
        $html = app('livewire')->mount($page);
        $check(strlen((string) $html) > 100, 'Page did not render: '.$page);
    }
    $field = App\Support\InventoryPhotoField::make();
    $check($field->getMaxFiles() === 6, 'Gallery file limit missing.');
    echo "PASS: galleries save, covers fall back, private access enforced, path tampering rejected, all photo pages render.\n";
} finally {
    $disk->deleteDirectory('vault-photos');
    @rmdir(config('filesystems.disks.local.root'));
}
