<?php

namespace Tests\Feature\Filament\Resources\CreditApplications;

use App\Enums\CreditApplicationStatus;
use App\Enums\CreditVerification;
use App\Filament\Resources\CreditApplications\Pages\ViewCreditApplication;
use App\Filament\Resources\CreditApplications\RelationManagers\InstallmentsRelationManager;
use App\Filament\Resources\CreditCustomers\Pages\ManageCreditCustomers;
use App\Models\CashAccount;
use App\Models\CreditApplication;
use App\Models\ExchangeRate;
use App\Models\User;
use Database\Seeders\SystemSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreditAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);
        ExchangeRate::factory()->create(['effective_date' => '2026-01-01', 'jpy_to_idr' => 110]);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_verifies_a_customer_who_asked_on_whatsapp(): void
    {
        $this->actingAs($this->admin);
        $customer = User::factory()->create(['credit_status' => CreditVerification::Diajukan, 'credit_requested_at' => now()]);
        $staff = User::factory()->staff()->create();

        Livewire::test(ManageCreditCustomers::class)
            ->assertCanSeeTableRecords([$customer])
            ->assertCanNotSeeTableRecords([$staff])
            ->callAction(TestAction::make('verify')->table($customer), ['limit' => 250000, 'note' => 'Zairyu card OK'])
            ->assertNotified('Pelanggan terverifikasi untuk kredit');

        $this->assertSame(CreditVerification::Terverifikasi, $customer->refresh()->credit_status);
        $this->assertSame(250000, $customer->credit_limit);
    }

    public function test_admin_approves_and_records_the_down_payment(): void
    {
        $this->actingAs($this->admin);
        $application = CreditApplication::factory()->create();

        Livewire::test(ViewCreditApplication::class, ['record' => $application->getRouteKey()])
            ->callAction('approve', ['start' => '2026-10-05'])
            ->assertNotified('Kredit disetujui, jadwal pembayaran dibuat');

        $application->refresh();
        $this->assertSame(CreditApplicationStatus::Disetujui, $application->status);
        $downPayment = $application->installments()->first();

        Livewire::test(InstallmentsRelationManager::class, ['ownerRecord' => $application, 'pageClass' => ViewCreditApplication::class])
            ->callAction(TestAction::make('pay')->table($downPayment), [
                'cash_account_id' => CashAccount::where('name', 'Bank Jepang')->value('id'),
                'paid_on' => now('Asia/Tokyo')->toDateString(),
            ])
            ->assertNotified('Pembayaran dicatat');

        $this->assertSame(CreditApplicationStatus::Berjalan, $application->refresh()->status);
        $this->assertNotNull($downPayment->refresh()->paid_at);
    }

    public function test_staff_cannot_open_the_credit_pages(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $this->get('/admin/credit-applications')->assertForbidden();
        $this->get('/admin/credit-customers')->assertForbidden();
    }
}
