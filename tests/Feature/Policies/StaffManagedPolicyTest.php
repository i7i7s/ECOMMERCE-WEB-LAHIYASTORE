<?php

namespace Tests\Feature\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffManagedPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string|null, bool, bool}>
     */
    public static function users(): array
    {
        return [
            'admin' => ['admin', true, true],
            'staff' => ['staff', true, true],
            'customer' => [null, true, false],
            'inactive staff' => ['staff', false, false],
        ];
    }

    #[DataProvider('users')]
    public function test_admin_and_staff_manage_and_delete_catalog(?string $role, bool $isActive, bool $canManage): void
    {
        $factory = User::factory();
        $user = ($role ? $factory->{$role}() : $factory)->create(['is_active' => $isActive]);
        $product = Product::factory()->create();

        $this->assertSame($canManage, $user->can('viewAny', Product::class));
        $this->assertSame($canManage, $user->can('create', Product::class));
        $this->assertSame($canManage, $user->can('update', $product));
        $this->assertSame($canManage, $user->can('delete', $product));
        $this->assertFalse($user->can('deleteAny', Product::class));
    }

    public function test_staff_can_open_products_in_admin_panel(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/admin/products')
            ->assertOk();
    }
}
