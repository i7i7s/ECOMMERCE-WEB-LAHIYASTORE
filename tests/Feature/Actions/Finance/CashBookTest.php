<?php

namespace Tests\Feature\Actions\Finance;

use App\Actions\Finance\ReconcileCashAccount;
use App\Actions\Finance\RecordExpense;
use App\Actions\Finance\TransferBetweenAccounts;
use App\Actions\Finance\VoidCashTransaction;
use App\Actions\Payments\AcceptPayment;
use App\Enums\CashFlow;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\ExchangeRate;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PreorderBatch;
use App\Models\Supplier;
use App\Models\TransactionCategory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Covers RecordExpense, TransferBetweenAccounts, VoidCashTransaction and
 * ReconcileCashAccount, which together keep the cash book (PRD §6.7).
 */
class CashBookTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    private CashAccount $yen;

    private CashAccount $bca;

    private CarbonImmutable $today;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);
        ExchangeRate::factory()->create(['effective_date' => '2026-09-28', 'jpy_to_idr' => 104]);
        $this->today = CarbonImmutable::parse('2026-10-01', 'Asia/Tokyo');
        $this->admin = User::factory()->admin()->create();
        $this->yen = CashAccount::where('name', 'Bank Jepang')->sole();
        $this->bca = CashAccount::where('name', 'BCA')->sole();
    }

    public function test_vendor_purchase_is_booked_against_batch_and_supplier(): void
    {
        $batch = PreorderBatch::factory()->create();
        $vendor = Supplier::factory()->create();

        $expense = app(RecordExpense::class)->handle($this->bca, TransactionCategory::system('belanja_vendor'), 5_000_000, $this->today, $this->admin, [
            'preorder_batch_id' => $batch->id,
            'supplier_id' => $vendor->id,
        ]);

        $this->assertSame(CashFlow::Keluar, $expense->type);
        $this->assertSame(5_000_000, $expense->amount_base);
        $this->assertTrue($expense->batch->is($batch));
        $this->assertTrue($expense->supplier->is($vendor));
        $this->assertSame(-5_000_000, $this->bca->balance());
    }

    public function test_yen_expense_is_converted_to_base_currency_with_the_rate_of_that_day(): void
    {
        $expense = app(RecordExpense::class)->handle($this->yen, TransactionCategory::system('ongkir_domestik'), 800, $this->today, $this->admin);

        $this->assertSame('104.0000', $expense->exchange_rate);
        $this->assertSame(83200, $expense->amount_base);
    }

    public function test_income_and_transfer_categories_cannot_be_used_as_expenses(): void
    {
        foreach (['penjualan', 'refund', 'transfer_keluar'] as $slug) {
            try {
                app(RecordExpense::class)->handle($this->yen, TransactionCategory::system($slug), 100, $this->today, $this->admin);
                $this->fail("{$slug} should be rejected.");
            } catch (BusinessRuleException) {
                $this->assertSame(0, CashTransaction::count());
            }
        }
    }

    public function test_exchanging_yen_to_rupiah_records_two_paired_transactions_with_actual_amounts(): void
    {
        [$out, $in] = app(TransferBetweenAccounts::class)->handle($this->yen, $this->bca, 200_000, 20_800_000, $this->today, $this->admin);

        $this->assertSame($out->transfer_group, $in->transfer_group);
        $this->assertSame('transfer_keluar', $out->category->slug);
        $this->assertSame('transfer_masuk', $in->category->slug);
        $this->assertSame(-200_000, $this->yen->balance());
        $this->assertSame(20_800_000, $this->bca->balance());
        // ¥200.000 × Rp104 = Rp20.800.000 out; Rp20.800.000 in: no exchange difference.
        $this->assertSame($out->amount_base, $in->amount_base);
    }

    public function test_transfer_needs_two_different_accounts(): void
    {
        $this->expectExceptionMessage('Akun asal dan tujuan harus berbeda.');

        app(TransferBetweenAccounts::class)->handle($this->yen, $this->yen, 100, 100, $this->today, $this->admin);
    }

    public function test_voiding_one_side_of_a_transfer_voids_both_and_restores_balances(): void
    {
        [$out] = app(TransferBetweenAccounts::class)->handle($this->yen, $this->bca, 200_000, 20_800_000, $this->today, $this->admin);

        app(VoidCashTransaction::class)->handle($out, $this->admin, 'Salah nominal');

        $this->assertSame(2, CashTransaction::whereNotNull('voided_at')->where('void_reason', 'Salah nominal')->count());
        $this->assertSame(0, $this->yen->balance());
        $this->assertSame(0, $this->bca->balance());
    }

    public function test_sales_income_cannot_be_voided_from_the_cash_book(): void
    {
        $order = Order::factory()->status(OrderStatus::MenungguVerifikasi)->create(['exchange_rate' => 104]);
        $payment = app(AcceptPayment::class)->handle(Payment::factory()->for($order)->for($this->yen)->create(), $this->admin);

        $this->expectExceptionMessage('dikoreksi lewat order');

        app(VoidCashTransaction::class)->handle($payment->cashTransaction, $this->admin, 'Coba');
    }

    public function test_staff_cannot_void_transactions(): void
    {
        $expense = app(RecordExpense::class)->handle($this->yen, TransactionCategory::system('ongkir_domestik'), 800, $this->today, $this->admin);

        $this->expectException(BusinessRuleException::class);

        app(VoidCashTransaction::class)->handle($expense, User::factory()->staff()->create(), 'Coba');
    }

    public function test_reconciliation_records_the_difference_with_the_cash_book(): void
    {
        app(TransferBetweenAccounts::class)->handle($this->yen, $this->bca, 10_000, 1_040_000, $this->today, $this->admin);

        $reconciliation = app(ReconcileCashAccount::class)->handle($this->bca, 1_000_000, $this->admin, 'Biaya admin bank?');

        $this->assertSame(1_040_000, $reconciliation->system_balance);
        $this->assertSame(1_000_000, $reconciliation->actual_balance);
        $this->assertSame(-40_000, $reconciliation->difference);
    }
}
