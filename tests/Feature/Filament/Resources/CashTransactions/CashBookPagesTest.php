<?php

namespace Tests\Feature\Filament\Resources\CashTransactions;

use App\Filament\Resources\CashTransactions\Pages\ManageCashTransactions;
use App\Models\CashAccount;
use App\Models\ExchangeRate;
use App\Models\TransactionCategory;
use App\Models\User;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashBookPagesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);
        ExchangeRate::factory()->create(['effective_date' => '2026-01-01', 'jpy_to_idr' => 105]);
    }

    public function test_admin_records_an_expense_from_the_cash_book(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $account = CashAccount::where('name', 'Kas Tunai')->sole();

        Livewire::test(ManageCashTransactions::class)
            ->callAction('expense', [
                'transaction_category_id' => TransactionCategory::system('ongkir_domestik')->id,
                'cash_account_id' => $account->id,
                'amount' => 1200,
                'date' => now('Asia/Tokyo')->toDateString(),
                'description' => 'Ongkir Yamato',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Pengeluaran dicatat');

        $this->assertSame(-1200, $account->balance());
    }

    public function test_staff_cannot_open_finance_pages(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        foreach (['/admin/cash-transactions', '/admin/cash-accounts', '/admin/cash-reconciliations'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }
}
