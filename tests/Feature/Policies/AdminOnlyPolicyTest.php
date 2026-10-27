<?php

namespace Tests\Feature\Policies;

use App\Models\CashAccount;
use App\Models\Order;
use App\Models\PhoneModel;
use App\Models\TransactionCategory;
use App\Models\User;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminOnlyPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string|null, bool, bool}>
     */
    public static function users(): array
    {
        return [
            'admin' => ['admin', true, true],
            'inactive admin' => ['admin', false, false],
            'staff' => ['staff', true, false],
            'customer' => [null, true, false],
        ];
    }

    #[DataProvider('users')]
    public function test_only_active_admin_manages_phone_models_and_prices(?string $role, bool $isActive, bool $canManage): void
    {
        $factory = User::factory();
        $user = ($role ? $factory->{$role}() : $factory)->create(['is_active' => $isActive]);
        $phoneModel = PhoneModel::factory()->create();

        $this->assertSame($canManage, $user->can('viewAny', PhoneModel::class));
        $this->assertSame($canManage, $user->can('create', PhoneModel::class));
        $this->assertSame($canManage, $user->can('update', $phoneModel));
        $this->assertSame($canManage, $user->can('delete', $phoneModel));
    }

    public function test_orders_staff_accounts_cash_and_system_categories_are_never_deleted(): void
    {
        $this->seed(SystemSeeder::class);
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('delete', Order::factory()->create()));
        $this->assertFalse($admin->can('delete', User::factory()->staff()->create()));
        $this->assertFalse($admin->can('delete', CashAccount::first()));
        $this->assertFalse($admin->can('delete', TransactionCategory::system('penjualan')));
        $this->assertTrue($admin->can('delete', TransactionCategory::factory()->create(['is_system' => false])));
    }

    public function test_staff_is_forbidden_from_phone_models_page(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/admin/phone-models')
            ->assertForbidden();
    }

    public function test_staff_is_forbidden_from_staff_accounts_page(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/admin/users')
            ->assertForbidden();
    }
}
