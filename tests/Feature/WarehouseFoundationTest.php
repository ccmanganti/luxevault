<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\QuickAdd;
use App\Filament\Resources\ItemResource\Pages\ManageItems;
use App\Filament\Resources\UserResource\Pages\ManageUsers;
use App\Filament\Widgets\InventoryTable;
use App\Models\InventoryGroup;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountWriter;
use App\Services\InventoryWriter;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class WarehouseFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function owner(string $plan = 'free'): User
    {
        $u = User::factory()->create();
        $u->plan = $plan;
        $u->save();
        $u->assignRole('warehouse_owner');

        return $u;
    }

    private function warehouse(User $u, string $name = 'Main'): Warehouse
    {
        return app(InventoryWriter::class)->save($u, Warehouse::class, ['name' => $name]);
    }

    private function item(User $u, Warehouse $w, array $extra = []): Item
    {
        return app(InventoryWriter::class)->save($u, Item::class, array_merge(['name' => 'Oak chair', 'warehouse_id' => $w->id, 'category' => 'Furniture', 'quantity' => 2, 'low_stock_threshold' => 0, 'unit_cost' => 100, 'unit_price' => 200, 'status' => 'available', 'condition' => 'good'], $extra));
    }

    public function test_free_owner_can_only_create_one_warehouse(): void
    {
        $u = $this->owner();
        $this->warehouse($u);
        $this->expectException(ValidationException::class);
        $this->warehouse($u, 'Second');
    }

    public function test_free_owner_cannot_create_guest_even_with_forged_role(): void
    {
        $u = $this->owner();
        $this->expectException(ValidationException::class);
        app(AccountWriter::class)->save($u, ['name' => 'Guest', 'email' => 'guest@example.com', 'password' => 'a-secure-password', 'role' => 'superadmin']);
    }

    public function test_premium_staff_can_only_see_assigned_warehouses(): void
    {
        $u = $this->owner('premium');
        $a = $this->warehouse($u);
        $b = $this->warehouse($u, 'Second');
        $other = $this->warehouse($this->owner());
        $staff = app(AccountWriter::class)->save($u, ['name' => 'Guest', 'email' => 'guest@example.com', 'password' => 'a-secure-password', 'warehouse_ids' => [$a->id], 'role' => 'superadmin']);
        $this->assertTrue($staff->hasRole('staff'));
        $this->assertEquals([$a->id], Warehouse::visibleTo($staff)->pluck('id')->all());
        $this->item($staff, $a);
        $this->expectException(ModelNotFoundException::class);
        $this->item($staff, $b);
    }

    public function test_guest_cannot_be_assigned_to_another_owner(): void
    {
        $u = $this->owner('premium');
        $w = $this->warehouse($this->owner());
        $this->expectException(ValidationException::class);
        app(AccountWriter::class)->save($u, ['name' => 'Guest', 'email' => 'guest@example.com', 'password' => 'a-secure-password', 'warehouse_ids' => [$w->id]]);
    }

    public function test_group_must_match_item_warehouse(): void
    {
        $u = $this->owner('premium');
        $a = $this->warehouse($u);
        $b = $this->warehouse($u, 'Other');
        $p = app(InventoryWriter::class)->save($u, Project::class, ['name' => 'Residence', 'warehouse_id' => $a->id]);
        $g = app(InventoryWriter::class)->save($u, InventoryGroup::class, ['name' => 'Living', 'project_id' => $p->id, 'type' => 'room']);
        $this->expectException(ValidationException::class);
        $this->item($u, $b, ['inventory_group_id' => $g->id]);
    }

    public function test_owner_cannot_edit_foreign_item_or_view_label(): void
    {
        $a = $this->owner();
        $b = $this->owner();
        $item = $this->item($b, $this->warehouse($b));
        $this->actingAs($a)->get(route('items.label', $item))->assertForbidden();
        $this->expectException(AuthorizationException::class);
        app(InventoryWriter::class)->save($a, Item::class, ['name' => 'Stolen'], $item);
    }

    public function test_item_codes_and_audit_and_label(): void
    {
        $u = $this->owner();
        $item = $this->item($u, $this->warehouse($u));
        $this->assertStringStartsWith('LV-', $item->sku);
        $this->assertNotEmpty($item->qr_code);
        $this->assertDatabaseCount('inventory_events', 1);
        $this->actingAs($u)->get(route('items.label', $item))->assertOk()->assertSee('<svg', false);
    }

    public function test_scanner_lookup_does_not_expose_other_owners(): void
    {
        $a = $this->owner();
        $b = $this->owner();
        $item = $this->item($b, $this->warehouse($b), ['barcode' => '123456']);
        $this->actingAs($a);
        Livewire::test(QuickAdd::class)->call('scanLookup', '123456')->assertSet('matchedItemId', null);
    }

    public function test_dashboard_and_all_resources_render(): void
    {
        $u = $this->owner();
        $this->item($u, $this->warehouse($u));
        $this->actingAs($u);
        foreach (['/admin', '/admin/items', '/admin/warehouses', '/admin/projects', '/admin/inventory-groups', '/admin/users', '/admin/quick-add'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_public_auth_pages_render(): void
    {
        foreach (['/', '/admin/login', '/admin/register', '/admin/password-reset/request'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_superadmin_sees_all_owners_and_can_activate_premium(): void
    {
        $admin = $this->owner();
        $admin->syncRoles('superadmin');
        $u = $this->owner();
        $this->warehouse($u);
        $this->assertEquals(1, Warehouse::visibleTo($admin)->count());
        app(AccountWriter::class)->save($admin, ['name' => $u->name, 'email' => $u->email, 'plan' => 'premium', 'is_active' => true], $u);
        $this->assertEquals('premium', $u->fresh()->plan);
    }

    public function test_deactivated_staff_loses_access(): void
    {
        $u = $this->owner('premium');
        $w = $this->warehouse($u);
        $staff = app(AccountWriter::class)->save($u, ['name' => 'Guest', 'email' => 'guest@example.com', 'password' => 'a-secure-password', 'warehouse_ids' => [$w->id]]);
        $staff->is_active = false;
        $staff->save();
        $this->assertEquals(0, Warehouse::visibleTo($staff)->count());
        $this->actingAs($staff)->get('/admin')->assertForbidden();
    }

    public function test_cannot_downgrade_owner_with_multiple_warehouses(): void
    {
        $admin = $this->owner();
        $admin->syncRoles('superadmin');
        $u = $this->owner('premium');
        $this->warehouse($u);
        $this->warehouse($u, 'Second');
        $this->expectException(ValidationException::class);
        app(AccountWriter::class)->save($admin, ['name' => $u->name, 'email' => $u->email, 'plan' => 'free'], $u);
    }

    public function test_delete_nonempty_warehouse_is_blocked(): void
    {
        $u = $this->owner();
        $w = $this->warehouse($u);
        $this->item($u, $w);
        $this->expectException(ValidationException::class);
        app(InventoryWriter::class)->delete($u, $w);
    }

    public function test_filament_create_and_edit_item_actions(): void
    {
        $u = $this->owner();
        $w = $this->warehouse($u);
        $this->actingAs($u);
        Livewire::test(ManageItems::class)->callAction('create', data: ['name' => 'Floor lamp', 'warehouse_id' => $w->id, 'category' => 'Lighting', 'condition' => 'good', 'status' => 'available', 'quantity' => 3, 'low_stock_threshold' => 0, 'unit_cost' => 50, 'unit_price' => 80])->assertHasNoFormErrors();
        $item = Item::firstOrFail();
        $this->assertEquals('Floor lamp', $item->name);
        Livewire::test(InventoryTable::class)->callAction(TestAction::make('edit')->table($item), data: ['name' => 'Brass floor lamp', 'warehouse_id' => $w->id, 'category' => 'Lighting', 'condition' => 'good', 'status' => 'available', 'quantity' => 4, 'low_stock_threshold' => 0, 'unit_cost' => 50, 'unit_price' => 80])->assertHasNoFormErrors();
        $this->assertEquals(4, $item->fresh()->quantity);
    }

    public function test_registration_assigns_free_owner_role(): void
    {
        Livewire::test(Register::class)->fillForm(['name' => 'New Owner', 'email' => 'new@example.com', 'password' => 'secure-test-password', 'passwordConfirmation' => 'secure-test-password'])->call('register')->assertHasNoFormErrors();
        $u = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue($u->hasRole('warehouse_owner'));
        $this->assertEquals('free', $u->plan);
        $this->assertTrue($u->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_guest_creation_and_deactivation_via_filament(): void
    {
        $u = $this->owner('premium');
        $w = $this->warehouse($u);
        $this->actingAs($u);
        Livewire::test(ManageUsers::class)->callAction('create', data: ['name' => 'Staff', 'email' => 'staff@example.com', 'password' => 'secure-test-password', 'warehouse_ids' => [$w->id], 'is_active' => true])->assertHasNoFormErrors();
        $staff = User::where('email', 'staff@example.com')->firstOrFail();
        $this->assertTrue($staff->hasRole('staff'));
        $this->assertEquals([$w->id], $staff->warehouses()->pluck('warehouses.id')->all());
        Livewire::test(ManageUsers::class)->callAction(TestAction::make('edit')->table($staff), data: ['is_active' => false])->assertHasNoFormErrors();
        $this->assertFalse($staff->fresh()->is_active);
    }

    public function test_quick_add_persists_item(): void
    {
        $u = $this->owner();
        $w = $this->warehouse($u);
        $this->actingAs($u);
        Livewire::test(QuickAdd::class)->fillForm(['name' => 'New vase', 'warehouse_id' => $w->id, 'category' => 'Accessories', 'condition' => 'good', 'quantity' => 1, 'unit_cost' => 20, 'unit_price' => 30])->call('save')->assertHasNoFormErrors()->assertRedirect('/admin/items');
        $this->assertDatabaseHas('items', ['name' => 'New vase', 'warehouse_id' => $w->id]);
    }

    public function test_clearing_sku_on_edit_preserves_existing_label(): void
    {
        $u = $this->owner();
        $item = $this->item($u, $this->warehouse($u));
        $original = $item->sku;
        app(InventoryWriter::class)->save($u, Item::class, array_merge($item->getAttributes(), ['sku' => null, 'name' => 'Updated chair']), $item);
        $this->assertSame($original, $item->fresh()->sku);
    }

    public function test_moving_item_cannot_retain_a_group_from_old_warehouse(): void
    {
        $u = $this->owner('premium');
        $a = $this->warehouse($u);
        $b = $this->warehouse($u, 'Other');
        $p = app(InventoryWriter::class)->save($u, Project::class, ['name' => 'Residence', 'warehouse_id' => $a->id]);
        $g = app(InventoryWriter::class)->save($u, InventoryGroup::class, ['name' => 'Living', 'project_id' => $p->id, 'type' => 'room']);
        $item = $this->item($u, $a, ['inventory_group_id' => $g->id]);
        $data = $item->getAttributes();
        unset($data['inventory_group_id']);
        $data['warehouse_id'] = $b->id;
        $this->expectException(ValidationException::class);
        app(InventoryWriter::class)->save($u, Item::class, $data, $item);
    }
}
