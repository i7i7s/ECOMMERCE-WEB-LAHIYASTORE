<?php

namespace Tests\Feature\Actions\Credit;

use App\Actions\Credit\ApproveCreditApplication;
use App\Actions\Credit\CloseCreditApplication;
use App\Actions\Credit\DecideCreditVerification;
use App\Actions\Credit\RecordCreditPayment;
use App\Actions\Credit\RequestCreditVerification;
use App\Actions\Credit\SubmitCreditApplication;
use App\Actions\Credit\UndoCreditPayment;
use App\Actions\Finance\VoidCashTransaction;
use App\Actions\Orders\PlaceOrder;
use App\Enums\CreditApplicationStatus;
use App\Enums\CreditVerification;
use App\Enums\Currency;
use App\Enums\OrderSource;
use App\Exceptions\BusinessRuleException;
use App\Models\CashAccount;
use App\Models\CreditApplication;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CreditFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $admin;

    private ProductVariant $phone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-06 10:00', 'Asia/Tokyo'));
        $this->seed(SystemSeeder::class);
        ExchangeRate::factory()->create(['effective_date' => '2026-01-01', 'jpy_to_idr' => 110]);
        $this->admin = User::factory()->admin()->create();
        $this->phone = ProductVariant::factory()->phone('Deep Blue', 256, 150000)->for(Product::factory()->credit()->create(['name' => 'iPhone 17 Pro']))->create();
    }

    private function submit(User $customer, int $downPayment = 30000, int $tenor = 12): CreditApplication
    {
        return app(SubmitCreditApplication::class)->handle($customer, $this->phone, $downPayment, $tenor);
    }

    private function approved(): CreditApplication
    {
        $application = $this->submit(User::factory()->creditVerified()->create());

        return app(ApproveCreditApplication::class)->handle($application, $this->admin, CarbonImmutable::parse('2026-10-05', 'Asia/Tokyo'));
    }

    public function test_customer_asks_for_verification_and_admin_verifies_with_a_limit(): void
    {
        $customer = User::factory()->create();

        app(RequestCreditVerification::class)->handle($customer);
        $this->assertSame(CreditVerification::Diajukan, $customer->refresh()->credit_status);
        $this->assertNotNull($customer->credit_requested_at);

        app(DecideCreditVerification::class)->verify($customer, $this->admin, 200000, 'KTP & zairyu card dicek');

        $this->assertTrue($customer->refresh()->canBuyOnCredit());
        $this->assertSame(200000, $customer->credit_limit);
        $this->assertTrue($customer->creditVerifier->is($this->admin));
    }

    public function test_staff_cannot_verify_credit(): void
    {
        $this->expectException(BusinessRuleException::class);

        app(DecideCreditVerification::class)->verify(User::factory()->create(), User::factory()->staff()->create());
    }

    public function test_unverified_customer_cannot_apply(): void
    {
        $this->expectExceptionMessage('belum terverifikasi');

        $this->submit(User::factory()->create(['phone' => '08011112222']));
    }

    public function test_verified_customer_applies_with_prices_and_terms_read_from_the_store(): void
    {
        $customer = User::factory()->creditVerified()->create(['name' => 'Raka']);

        $application = $this->submit($customer, 30000, 12);

        $this->assertSame(CreditApplicationStatus::Diajukan, $application->status);
        $this->assertSame('iPhone 17 Pro · 256 GB · Deep Blue', $application->item_name);
        $this->assertSame(150000, $application->cash_price);
        // ¥120.000 financed + 2% × 12 months = ¥148.800 → ¥12.400 per month.
        $this->assertSame(12400, $application->monthly_installment);
        $this->assertSame(30000 + 12 * 12400, $application->total_payable);
        $this->assertSame('Raka', $application->customer_name);
        $this->assertMatchesRegularExpression('/^KR-\d{8}-001$/', $application->code);
    }

    public function test_rupiah_credit_uses_the_rupiah_price_and_rupiah_rounding(): void
    {
        $application = app(SubmitCreditApplication::class)->handle(User::factory()->creditVerified()->create(), $this->phone, 3300000, 12, currency: Currency::Idr);

        // Rp16.500.000; Rp13.200.000 financed + 2% × 12 = Rp16.368.000 → Rp1.364.000 per month.
        $this->assertSame(Currency::Idr, $application->currency);
        $this->assertSame(16500000, $application->cash_price);
        $this->assertSame(1364000, $application->monthly_installment);
        $this->assertSame('110.0000', $application->exchange_rate);

        $this->expectExceptionMessage('DP minimal Rp3.300.000');
        app(SubmitCreditApplication::class)->handle(User::factory()->creditVerified()->create(), $this->phone, 3200000, 12, currency: Currency::Idr);
    }

    public function test_rupiah_credit_counts_against_the_yen_limit_and_is_paid_into_a_rupiah_account(): void
    {
        $customer = User::factory()->creditVerified(200000)->create();
        $rupiah = app(SubmitCreditApplication::class)->handle($customer, $this->phone, 3300000, 12, currency: Currency::Idr);

        // Rp13.200.000 / 110 = ¥120.000 of the ¥200.000 limit is used.
        $this->assertSame(120000, $customer->openCreditFinanced());

        $approved = app(ApproveCreditApplication::class)->handle($rupiah, $this->admin);
        $downPayment = $approved->installments()->first();

        try {
            app(RecordCreditPayment::class)->handle($downPayment, CashAccount::where('name', 'Bank Jepang')->sole(), CarbonImmutable::now('Asia/Tokyo'), $this->admin);
            $this->fail('A yen account must not take a rupiah payment.');
        } catch (BusinessRuleException $exception) {
            $this->assertSame('Pilih akun kas IDR yang aktif.', $exception->getMessage());
        }

        $paid = app(RecordCreditPayment::class)->handle($downPayment, CashAccount::where('name', 'BCA')->sole(), CarbonImmutable::now('Asia/Tokyo'), $this->admin);
        $this->assertSame(Currency::Idr, $paid->cashTransaction->currency);
        $this->assertSame(3300000, $paid->cashTransaction->amount_base);
    }

    public function test_down_payment_tenor_and_sale_mode_are_checked(): void
    {
        $customer = User::factory()->creditVerified()->create();

        foreach ([[29000, 12, 'DP minimal'], [30000, 7, 'tenor'], [150000, 12, 'lebih kecil dari harga']] as [$downPayment, $tenor, $message]) {
            try {
                $this->submit($customer, $downPayment, $tenor);
                $this->fail("Expected: {$message}");
            } catch (BusinessRuleException $exception) {
                $this->assertStringContainsString($message, $exception->getMessage());
            }
        }

        $this->expectExceptionMessage('tidak tersedia untuk kredit');
        app(SubmitCreditApplication::class)->handle($customer, ProductVariant::factory()->create(), 1000, 12);
    }

    public function test_credit_limit_counts_every_open_credit(): void
    {
        $customer = User::factory()->creditVerified(200000)->create();
        $this->submit($customer, 30000);

        $this->expectExceptionMessage('melebihi sisa batas kredit');

        $this->submit($customer, 30000);
    }

    public function test_settings_change_the_terms_of_new_applications(): void
    {
        Setting::create(['key' => 'credit_tenors', 'value' => '6,24']);
        Setting::create(['key' => 'credit_margin_percent', 'value' => '1.5']);
        Setting::create(['key' => 'credit_admin_fee', 'value' => '3000']);

        $application = $this->submit(User::factory()->creditVerified()->create(), 30000, 24);

        // ¥120.000 + 1,5% × 24 (¥43.200) + ¥3.000 admin = ¥166.200 / 24 → ¥7.000 (rounded up to ¥100).
        $this->assertSame(7000, $application->monthly_installment);
        $this->assertSame(3000, $application->admin_fee);
    }

    public function test_approval_creates_the_down_payment_and_monthly_schedule(): void
    {
        $application = $this->approved();

        $this->assertSame(CreditApplicationStatus::Disetujui, $application->status);
        $schedule = $application->installments()->get();
        $this->assertCount(13, $schedule);
        $this->assertSame(['2026-10-05', 30000], [$schedule[0]->due_date->toDateString(), $schedule[0]->amount]);
        $this->assertSame(['2026-11-05', 12400], [$schedule[1]->due_date->toDateString(), $schedule[1]->amount]);
        $this->assertSame('2027-10-05', $schedule[12]->due_date->toDateString());
    }

    public function test_paying_the_down_payment_books_income_and_starts_the_credit(): void
    {
        $application = $this->approved();
        $account = CashAccount::where('name', 'Bank Jepang')->sole();

        $installment = app(RecordCreditPayment::class)->handle($application->installments()->first(), $account, CarbonImmutable::parse('2026-10-05', 'Asia/Tokyo'), $this->admin);

        $this->assertSame(CreditApplicationStatus::Berjalan, $application->refresh()->status);
        $transaction = $installment->cashTransaction;
        $this->assertSame('kredit_hp', $transaction->category->slug);
        $this->assertSame(30000, $transaction->amount);
        $this->assertSame(3300000, $transaction->amount_base);
        $this->assertSame(30000, $account->balance());
    }

    public function test_payments_go_in_order_and_the_last_one_closes_the_credit(): void
    {
        $application = $this->approved();
        $account = CashAccount::where('name', 'Bank Jepang')->sole();
        $schedule = $application->installments()->get();
        $paidOn = CarbonImmutable::parse('2026-10-05', 'Asia/Tokyo');

        try {
            app(RecordCreditPayment::class)->handle($schedule[2], $account, $paidOn, $this->admin);
            $this->fail('Paying out of order should fail.');
        } catch (BusinessRuleException $exception) {
            $this->assertSame('Catat DP dulu.', $exception->getMessage());
        }

        foreach ($schedule as $installment) {
            app(RecordCreditPayment::class)->handle($installment->refresh(), $account, $paidOn, $this->admin);
        }

        $this->assertSame(CreditApplicationStatus::Lunas, $application->refresh()->status);
        $this->assertNotNull($application->completed_at);
        $this->assertSame($application->total_payable, $application->paidAmount());
    }

    public function test_undoing_the_latest_payment_voids_its_income(): void
    {
        $application = $this->approved();
        $account = CashAccount::where('name', 'Bank Jepang')->sole();
        $downPayment = app(RecordCreditPayment::class)->handle($application->installments()->first(), $account, CarbonImmutable::now('Asia/Tokyo'), $this->admin);
        $transaction = $downPayment->cashTransaction;

        app(UndoCreditPayment::class)->handle($downPayment, $this->admin, 'Salah catat');

        $this->assertNull($downPayment->refresh()->paid_at);
        $this->assertNotNull($transaction->refresh()->voided_at);
        $this->assertSame(0, $account->balance());
        $this->assertSame(CreditApplicationStatus::Disetujui, $application->refresh()->status);
    }

    public function test_credit_income_cannot_be_voided_from_the_cash_book(): void
    {
        $application = $this->approved();
        $installment = app(RecordCreditPayment::class)->handle($application->installments()->first(), CashAccount::where('name', 'Bank Jepang')->sole(), CarbonImmutable::now('Asia/Tokyo'), $this->admin);

        $this->expectExceptionMessage('menu Kredit');

        app(VoidCashTransaction::class)->handle($installment->cashTransaction, $this->admin, 'Coba');
    }

    public function test_rupiah_account_cannot_take_credit_payments(): void
    {
        $application = $this->approved();

        $this->expectExceptionMessage('Pilih akun kas JPY yang aktif.');

        app(RecordCreditPayment::class)->handle($application->installments()->first(), CashAccount::where('name', 'BCA')->sole(), CarbonImmutable::now('Asia/Tokyo'), $this->admin);
    }

    public function test_customer_cancels_while_reviewed_but_not_after_approval(): void
    {
        $customer = User::factory()->creditVerified()->create();
        $application = $this->submit($customer);

        app(CloseCreditApplication::class)->cancel($application, $customer, 'Berubah pikiran');
        $this->assertSame(CreditApplicationStatus::Dibatalkan, $application->refresh()->status);

        $approved = $this->approved();

        $this->expectExceptionMessage('lewat admin');
        app(CloseCreditApplication::class)->cancel($approved, $approved->user, 'Batal');
    }

    public function test_admin_rejects_a_new_application(): void
    {
        $application = $this->submit(User::factory()->creditVerified()->create());

        app(CloseCreditApplication::class)->reject($application, $this->admin, 'Data kerja belum lengkap');

        $this->assertSame(CreditApplicationStatus::Ditolak, $application->refresh()->status);
        $this->assertSame('Data kerja belum lengkap', $application->admin_note);
    }

    public function test_credit_products_cannot_be_ordered_through_the_cart(): void
    {
        $this->expectExceptionMessage('hanya dijual lewat kredit');

        app(PlaceOrder::class)->handle(
            ['name' => 'A', 'phone' => '08011112222', 'postal_code' => '1500001', 'prefecture' => 'Tokyo', 'city' => 'Shibuya', 'address_line1' => '1-1'],
            [['type' => 'product', 'name' => 'iPhone', 'qty' => 1, 'refs' => ['product_variant_id' => $this->phone->id]]],
            Currency::Jpy,
            OrderSource::Website,
        );
    }
}
