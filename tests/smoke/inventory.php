<?php
// Also supports an isolated source checkout that shares the installed vendor directory.
require_once __DIR__.'/../../vendor/autoload.php';
spl_autoload_register(function (string $class) {
    foreach (['App\\' => '/app/', 'Database\\Seeders\\' => '/database/seeders/'] as $prefix => $path) {
        if (str_starts_with($class, $prefix)) {
            $file = dirname(__DIR__, 2).$path.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
            if (is_file($file)) require $file;
        }
    }
}, true, true);
require __DIR__.'/photos.php'; // Fresh in-memory DB, fake users and a private test disk.
use App\Support\AccountCategories;
use App\Models\{Item, Warehouse, ItemCategory};
use App\Filament\Pages\BrowseInventory;

$check(AccountCategories::remember($owner->id,'Ceramics') === 'Ceramics','Category creation failed');
$check(AccountCategories::remember($owner->id,'  ceramics  ') === 'Ceramics','Category deduplication failed');
$check(ItemCategory::where('owner_id',$owner->id)->count()===1,'Duplicate category created');
$check(isset(AccountCategories::options($owner)['Ceramics']),'Saved category missing');
$check(!isset(AccountCategories::options($other)['Ceramics']),'Category leaked across accounts');
$check(isset(AccountCategories::options($owner)['Others']),'Others category missing');
$item->category='Legacy fabric';$item->save();
$check(isset(AccountCategories::options($owner)['Legacy fabric']),'Existing custom category not discovered');
$owner->plan='premium';$owner->save();
$staff=app(App\Services\AccountWriter::class)->save($owner,['name'=>'Test staff','email'=>'browse-staff@example.test','password'=>'unused-test-value','warehouse_ids'=>[$warehouse->id]]);
$check(isset(AccountCategories::options($staff)['Ceramics']),'Staff did not inherit account categories');
$foreignWarehouse=$writer->save($other,Warehouse::class,['name'=>'Foreign warehouse']);
try { AccountCategories::options($owner,$foreignWarehouse->id); throw new RuntimeException('Foreign category scope accepted'); } catch (Illuminate\Database\Eloquent\ModelNotFoundException $expected) {}
$page=new BrowseInventory;
$page->warehouseId=$warehouse->id;$page->projectId=$project->id;$page->groupId=$group->id;
$check($page->location()[2]->id===$group->id,'Tree did not resolve full path');
$page->warehouseId=$foreignWarehouse->id;
try { $page->location();throw new RuntimeException('Foreign tree scope accepted'); } catch (Illuminate\Database\Eloquent\ModelNotFoundException $expected) {}
$page->warehouseId=$warehouse->id;
$page->bootedInteractsWithTable();
$page->tableFilters=['place'=>['warehouse'=>$warehouse->id,'project'=>$project->id,'group'=>$group->id],'order'=>['by'=>'value_high']];
$check($page->getFilteredTableQuery()->count()===1,'Location filters failed');
$page->tableSearch='does-not-exist';
$check($page->getFilteredTableQuery()->count()===0,'Search did not filter items');
$page->tableSearch='';
$expensive=$writer->save($owner,Item::class,array_merge($data,['name'=>'Pricier chair','unit_price'=>500,'photos'=>[]]));
$check($page->getFilteredTableQuery()->first()->id===$expensive->id,'Value sort failed');
$page->tableFilters['order']['by']='value_low';
$check($page->getFilteredTableQuery()->first()->id===$item->id,'Ascending value sort failed');
foreach ([BrowseInventory::class, App\Filament\Pages\Auth\Settings::class, App\Filament\Widgets\InventoryTable::class] as $component) {
    $html=app('livewire')->mount($component);
    $check(strlen((string)$html)>100,'New page failed to render');
}
$settings=new App\Filament\Pages\Auth\Settings;
$settings->mount();
$html=$settings->form->toHtml();
$check(str_contains($html,'Profile photo or logo'),'Profile photo field absent');
$check(str_contains($html,'Change password'),'Password section absent');
$check(Filament\Facades\Filament::getPanel('admin')->getUserMenuItems()['profile']->getLabel()==='Settings','Settings menu label missing');
$check($owner->getFilamentAvatarUrl()===null,'Missing image must fall back to initials');
echo "PASS: account categories, staff scope, legacy discovery, tree authorization, search, location filters, price sorting, Settings and widget rendering.\n";
