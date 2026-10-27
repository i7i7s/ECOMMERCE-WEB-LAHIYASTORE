<?php

namespace Tests\Feature\Livewire\Account;

use App\Livewire\Account\Addresses;
use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AddressesTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['name' => 'Raka', 'phone' => '08012345678']);
        $this->actingAs($this->customer);
    }

    public function test_guest_is_sent_to_login(): void
    {
        auth()->logout();

        $this->get(route('account.addresses'))->assertRedirect(route('login'));
    }

    public function test_first_address_becomes_the_default(): void
    {
        Livewire::test(Addresses::class)
            ->call('create')
            ->assertSet('recipient_name', 'Raka')
            ->set('postal_code', '530-0001')
            ->set('prefecture', 'Osaka')
            ->set('city', 'Osaka-shi')
            ->set('address_line1', 'Umeda 1-2-3')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editingId', null);

        $address = $this->customer->addresses()->sole();
        $this->assertTrue($address->is_default);
        $this->assertSame('Osaka', $address->prefecture);
    }

    public function test_rejects_invalid_japanese_address(): void
    {
        Livewire::test(Addresses::class)
            ->call('create')
            ->set('postal_code', '12345')
            ->set('prefecture', 'Jakarta')
            ->call('save')
            ->assertHasErrors(['postal_code', 'prefecture', 'city', 'address_line1']);
    }

    public function test_making_another_address_default_unsets_the_old_one(): void
    {
        $home = Address::factory()->for($this->customer)->create(['is_default' => true]);
        $office = Address::factory()->for($this->customer)->create();

        Livewire::test(Addresses::class)->call('makeDefault', $office->id);

        $this->assertFalse($home->refresh()->is_default);
        $this->assertTrue($office->refresh()->is_default);
    }

    public function test_deleting_the_default_promotes_another_address(): void
    {
        $home = Address::factory()->for($this->customer)->create(['is_default' => true]);
        $office = Address::factory()->for($this->customer)->create();

        Livewire::test(Addresses::class)->call('delete', $home->id);

        $this->assertModelMissing($home);
        $this->assertTrue($office->refresh()->is_default);
    }

    public function test_cannot_touch_another_customers_address(): void
    {
        $someoneElse = Address::factory()->create();

        Livewire::test(Addresses::class)
            ->call('delete', $someoneElse->id)
            ->assertNotFound();

        $this->assertModelExists($someoneElse);
    }
}
