<?php

namespace Tests\Feature\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_list_shows_team_accounts_but_not_customers(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();
        $customer = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$admin, $staff])
            ->assertCanNotSeeTableRecords([$customer]);
    }

    public function test_admin_creates_staff_account_with_hashed_password(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Sinta', 'email' => 'sinta@lahiya.test', 'role' => 'staff', 'password' => 'rahasia123', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'sinta@lahiya.test')->sole();
        $this->assertSame(UserRole::Staff, $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_new_account_requires_password_of_at_least_8_characters(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Sinta', 'email' => 'sinta@lahiya.test', 'role' => 'staff', 'password' => 'pendek'])
            ->call('create')
            ->assertHasFormErrors(['password' => 'min']);
    }

    public function test_leaving_password_blank_keeps_the_old_password(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $staff = User::factory()->staff()->create(['password' => 'lama-sekali']);

        Livewire::test(EditUser::class, ['record' => $staff->getKey()])
            ->fillForm(['name' => 'Nama Baru', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $staff->refresh();
        $this->assertSame('Nama Baru', $staff->name);
        $this->assertTrue(Hash::check('lama-sekali', $staff->password));
    }

    public function test_admin_cannot_demote_or_deactivate_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getKey()])
            ->assertFormFieldDisabled('role')
            ->assertFormFieldDisabled('is_active')
            ->fillForm(['role' => 'staff', 'is_active' => false])
            ->call('save');

        $admin->refresh();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
    }
}
