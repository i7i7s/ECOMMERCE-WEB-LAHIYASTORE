<?php

namespace Tests\Feature\Models;

use App\Models\CashAccount;
use App\Models\CashTransaction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CashAccountTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_balance_is_incoming_minus_outgoing_ignoring_voided_transactions(): void
    {
        $account = CashAccount::factory()->create();
        CashTransaction::factory()->for($account)->create(['amount' => 10000]);
        CashTransaction::factory()->for($account)->create(['amount' => 2800]);
        CashTransaction::factory()->for($account)->outgoing()->create(['amount' => 3000]);
        CashTransaction::factory()->for($account)->voided()->create(['amount' => 50000]);
        CashTransaction::factory()->create(['amount' => 99999]);

        $this->assertSame(9800, $account->balance());
    }

    public function test_new_account_has_zero_balance(): void
    {
        $this->assertSame(0, CashAccount::factory()->create()->balance());
    }
}
