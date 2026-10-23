<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_warehouse_forms(): void
    {
        $owner = User::factory()->owner()->create();
        $warehouse = Warehouse::factory()->create();

        $this->actingAs($owner)->get(route('admin.warehouses.create'))->assertOk();
        $this->actingAs($owner)->get(route('admin.warehouses.edit', $warehouse))->assertOk();
        $this->actingAs($owner)->get(route('admin.settings.edit'))->assertOk();
    }

    public function test_first_warehouse_becomes_default(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->post(route('admin.warehouses.store'), [
            'name' => 'Gudang',
            'code' => 'gudang',
            'is_default' => 0,
            'is_active' => 1,
        ])->assertRedirect(route('admin.warehouses.index'));

        $this->assertDatabaseHas('warehouses', [
            'code' => 'gudang',
            'is_default' => true,
        ]);
    }

    public function test_only_one_warehouse_can_be_default(): void
    {
        $owner = User::factory()->owner()->create();
        $first = Warehouse::factory()->create(['is_default' => true]);
        $second = Warehouse::factory()->create(['is_default' => false]);

        $this->actingAs($owner)->put(route('admin.warehouses.update', $second), [
            'name' => $second->name,
            'code' => $second->code,
            'is_default' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('admin.warehouses.index'));

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_default_warehouse_cannot_be_deleted(): void
    {
        $owner = User::factory()->owner()->create();
        $warehouse = Warehouse::factory()->create(['is_default' => true]);

        $this->actingAs($owner)
            ->delete(route('admin.warehouses.destroy', $warehouse))
            ->assertSessionHasErrors('warehouse');

        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
    }
}
