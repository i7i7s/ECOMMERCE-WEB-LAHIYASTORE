<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string|null, bool, bool}>
     */
    public static function panelAccessCases(): array
    {
        return [
            'active admin' => ['admin', true, true],
            'active staff' => ['staff', true, true],
            'customer' => [null, true, false],
            'inactive admin' => ['admin', false, false],
            'inactive staff' => ['staff', false, false],
        ];
    }

    #[DataProvider('panelAccessCases')]
    public function test_only_active_admin_and_staff_can_access_the_admin_panel(?string $state, bool $isActive, bool $canAccess): void
    {
        $factory = User::factory();
        $factory = $state ? $factory->{$state}() : $factory;
        $user = $factory->create(['is_active' => $isActive]);

        $this->assertSame($canAccess, $user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_admin_panel_dashboard_loads_for_staff(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/admin')
            ->assertOk();
    }

    public function test_admin_panel_forbids_customers(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }
}
