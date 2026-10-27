<?php

namespace Tests\Feature\Livewire\Account;

use App\Livewire\Account\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_updates_name_email_and_phone(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);
        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->set('name', 'Raka Baru')
            ->set('email', 'Baru@Example.com')
            ->set('phone', '080-5555-6666')
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertSet('profileSaved', true);

        $user->refresh();
        $this->assertSame('Raka Baru', $user->name);
        $this->assertSame('baru@example.com', $user->email);
        $this->assertSame('08055556666', $user->phone);
    }

    public function test_rejects_email_used_by_another_account(): void
    {
        User::factory()->create(['email' => 'dipakai@example.com']);
        $this->actingAs(User::factory()->create());

        Livewire::test(Profile::class)
            ->set('email', 'dipakai@example.com')
            ->call('saveProfile')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);
        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->set('current_password', 'salah')
            ->set('password', 'barubaru123')
            ->set('password_confirmation', 'barubaru123')
            ->call('savePassword')
            ->assertHasErrors('current_password');

        Livewire::test(Profile::class)
            ->set('current_password', 'rahasia123')
            ->set('password', 'barubaru123')
            ->set('password_confirmation', 'barubaru123')
            ->call('savePassword')
            ->assertHasNoErrors()
            ->assertSet('passwordSaved', true);

        $this->assertTrue(Hash::check('barubaru123', $user->refresh()->password));
    }
}
