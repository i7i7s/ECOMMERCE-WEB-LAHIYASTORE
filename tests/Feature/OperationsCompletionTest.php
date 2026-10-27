<?php

namespace Tests\Feature;

use App\Actions\Batches\EnsureOpenBatch;
use App\Actions\Orders\PlaceOrder;
use App\Actions\Orders\RecordComplaint;
use App\Actions\Orders\ResolveComplaint;
use App\Actions\Payments\AcceptPayment;
use App\Actions\Payments\SubmitPaymentProof;
use App\Enums\BatchStatus;
use App\Enums\CashFlow;
use App\Enums\ComplaintResolution;
use App\Enums\ComplaintStatus;
use App\Enums\Currency;
use App\Enums\DesignReviewStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Filament\Pages\StoreSettings;
use App\Filament\Resources\OrderComplaints\OrderComplaintResource;
use App\Filament\Resources\OrderComplaints\Pages\ManageOrderComplaints;
use App\Filament\Widgets\BatchOverview;
use App\Filament\Widgets\FinanceOverview;
use App\Filament\Widgets\OperationsOverview;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\ExchangeRate;
use App\Models\Order;
use App\Models\OrderComplaint;
use App\Models\OrderItem;
use App\Models\OrderItemDesign;
use App\Models\Payment;
use App\Models\PreorderBatch;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Notifications\AdminOperationalNotification;
use App\Support\BatchSchedule;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OperationsCompletionTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        TransactionCategory::factory()->create(['slug' => 'refund', 'is_system' => true]);
        TransactionCategory::factory()->create(['slug' => 'penjualan', 'is_system' => true, 'type' => CashFlow::Masuk]);
        Storage::fake('local');
        $this->travelTo(CarbonImmutable::parse('2026-10-02 12:00', 'Asia/Tokyo'));
    }

    public function test_replacement_preserves_cost_and_design_but_never_books_income(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->status(OrderStatus::Dikirim)->create(['shipped_at' => now()->subDays(2)]);
        $item = OrderItem::factory()->for($order)->create(['qty' => 2, 'unit_cost_idr' => 70000, 'is_packed' => true]);
        $design = OrderItemDesign::factory()->approved()->for($item)->create();
        $complaint = OrderComplaint::factory()->for($order)->create();
        $resolved = app(ResolveComplaint::class)->handle($complaint, $admin, ComplaintResolution::CetakUlang, 'Kesalahan cetak vendor');

        $replacement = $resolved->replacementOrder;
        $this->assertSame(0, $replacement->total);
        $this->assertSame(OrderSource::Penggantian, $replacement->source);
        $this->assertSame($order->id, $replacement->parent_order_id);
        $this->assertSame(BatchStatus::Dibuka, $replacement->batch->status);
        $this->assertSame(70000, $replacement->items->sole()->unit_cost_idr);
        $this->assertFalse($replacement->items->sole()->is_packed);
        $this->assertSame(2, $replacement->items->sole()->qty);
        $this->assertSame($design->original_path, $replacement->items->sole()->design->original_path);
        $this->assertSame(DesignReviewStatus::Menunggu, $replacement->items->sole()->design->review_status);
        $this->assertNull($replacement->items->sole()->design->reviewed_at);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('cash_transactions', 0);
        $this->assertSame(OrderStatus::Dikirim, $order->refresh()->status);
        $this->assertSame(ComplaintStatus::Selesai, $resolved->status);
    }

    public function test_a_stale_complaint_cannot_create_a_second_replacement(): void
    {
        $admin = User::factory()->admin()->create();
        $complaint = OrderComplaint::factory()->create();
        OrderItem::factory()->for($complaint->order)->create();
        app(ResolveComplaint::class)->handle($complaint, $admin, ComplaintResolution::CetakUlang, 'Cetak ulang');
        try {
            app(ResolveComplaint::class)->handle($complaint, $admin, ComplaintResolution::CetakUlang, 'Ulang');
            $this->fail('Duplicate replacement was accepted.');
        } catch (BusinessRuleException) {
            $this->assertSame(1, Order::where('parent_order_id', $complaint->order_id)->count());
        }
    }

    public function test_refund_uses_original_currency_account_and_rate_and_cannot_repeat(): void
    {
        $admin = User::factory()->admin()->create();
        $complaint = OrderComplaint::factory()->create();
        $payment = Payment::factory()->for($complaint->order)->create(['status' => PaymentStatus::Diterima]);
        app(ResolveComplaint::class)->handle($complaint, $admin, ComplaintResolution::Refund, 'Transfer bank REF-12');
        $cash = CashTransaction::sole();
        $this->assertSame($payment->cash_account_id, $cash->cash_account_id);
        $this->assertSame($payment->currency, $cash->currency);
        $this->assertSame($payment->amount, $cash->amount);
        $this->assertSame(294000, $cash->amount_base);
        $this->assertSame(-2800, $payment->cashAccount->balance());

        $second = OrderComplaint::factory()->for($complaint->order)->create();
        try {
            app(ResolveComplaint::class)->handle($second, $admin, ComplaintResolution::Refund, 'Transfer ulang');
            $this->fail('Duplicate refund was accepted.');
        } catch (BusinessRuleException) {
            $this->assertDatabaseCount('cash_transactions', 1);
            $this->assertSame(ComplaintStatus::Terbuka, $second->refresh()->status);
        }
    }

    public function test_staff_cannot_resolve_complaints_even_by_direct_action(): void
    {
        $this->expectException(HttpException::class);
        app(ResolveComplaint::class)->handle(OrderComplaint::factory()->create(), User::factory()->staff()->create(), ComplaintResolution::Tolak, 'Alasan');
    }

    public function test_recording_complaint_requires_both_evidence_files_and_respects_deadline(): void
    {
        $staff = User::factory()->staff()->create();
        $order = Order::factory()->status(OrderStatus::Dikirim)->create(['shipped_at' => now()->subDays(4)]);
        Storage::disk('local')->put('complaints/videos/test.mp4', 'video');
        Storage::disk('local')->put('complaints/photos/test.jpg', 'photo');
        $data = [
            'type' => 'salah_cetak', 'description' => 'Desain berbeda', 'received_at' => now('Asia/Tokyo')->subDay()->toDateString(),
            'evidence_path' => 'complaints/videos/test.mp4', 'photo_path' => 'complaints/photos/test.jpg',
        ];
        $complaint = app(RecordComplaint::class)->handle($order, $staff, $data);
        $this->assertSame(ComplaintStatus::Terbuka, $complaint->status);

        $expired = Order::factory()->status(OrderStatus::Dikirim)->create(['shipped_at' => now()->subDays(11)]);
        $this->expectException(BusinessRuleException::class);
        app(RecordComplaint::class)->handle($expired, $staff, $data);
    }

    public function test_received_deadline_is_enforced_even_before_shipping_deadline(): void
    {
        $order = Order::factory()->status(OrderStatus::Dikirim)->create(['shipped_at' => now()->subDays(6)]);
        $this->expectException(BusinessRuleException::class);
        app(RecordComplaint::class)->handle($order, User::factory()->staff()->create(), [
            'type' => 'salah_cetak', 'description' => 'Terlambat',
            'received_at' => now('Asia/Tokyo')->subDays(4)->toDateString(),
            'evidence_path' => 'complaints/a.mp4', 'photo_path' => 'complaints/a.jpg',
        ]);
    }

    public function test_complaint_files_are_private_and_the_admin_form_renders_and_resolves(): void
    {
        $complaint = OrderComplaint::factory()->create(['evidence_path' => 'complaints/a.mp4']);
        Storage::disk('local')->put('complaints/a.mp4', 'test video');
        $this->actingAs(User::factory()->create())->get(route('admin.files.complaint', [$complaint, 'video']))->assertForbidden();
        $this->actingAs(User::factory()->staff()->create())->get(route('admin.files.complaint', [$complaint, 'video']))->assertOk();
        Livewire::test(ManageOrderComplaints::class)->assertCanSeeTableRecords([$complaint])
            ->assertActionHidden(TestAction::make('resolve')->table($complaint));
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(ManageOrderComplaints::class)
            ->callAction(TestAction::make('resolve')->table($complaint), ['resolution' => 'tolak', 'note' => 'Kesalahan pemilihan tipe oleh pelanggan'])
            ->assertHasNoActionErrors();
        $this->assertSame(ComplaintResolution::Tolak, $complaint->refresh()->resolution);
        $this->get(OrderComplaintResource::getUrl())->assertOk();
    }

    public function test_settings_save_validate_audit_and_take_effect_in_policy_and_schedule(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(StoreSettings::class)->fillForm([
            'batch_period' => 'weekly', 'batch_cutoff_day' => 'friday', 'batch_cutoff_time' => '18:30',
            'eta_min_days' => 10, 'eta_max_days' => 12, 'payment_window_hours' => 48,
            'policy_cancellation' => '<script>alert(1)</script> Teks kebijakan baru.',
            'store_whatsapp' => '+818012345678',
        ])->call('save')->assertHasNoFormErrors();
        $this->assertSame('48', Setting::valueOf('payment_window_hours'));
        $this->assertDatabaseCount('store_setting_changes', 1);
        $this->assertSame('2026-10-02 18:30', BatchSchedule::nextCutoff()->format('Y-m-d H:i'));
        $this->get(route('policy'))->assertOk()->assertSee('48 jam')->assertSee('Teks kebijakan baru.')
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('https://wa.me/818012345678', false);

        Livewire::test(StoreSettings::class)->fillForm(['eta_min_days' => 50, 'eta_max_days' => 10])
            ->call('save')->assertHasFormErrors(['eta_max_days']);
        $this->assertSame('12', Setting::valueOf('eta_max_days'));
    }

    public function test_staff_and_customers_cannot_open_settings_or_finance_widget(): void
    {
        $this->actingAs(User::factory()->staff()->create())->get(StoreSettings::getUrl())->assertForbidden();
        $this->assertFalse(FinanceOverview::canView());
        Livewire::test(FinanceOverview::class)->assertForbidden();
        $this->actingAs(User::factory()->create())->get(StoreSettings::getUrl())->assertForbidden();
    }

    public function test_populated_dashboard_renders_operational_widgets_and_admin_finances(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $batch = PreorderBatch::factory()->create(['cutoff_at' => now()->addDay()]);
        $order = Order::factory()->paid($batch)->create();
        OrderItem::factory()->for($order)->create(['qty' => 3]);
        CashAccount::factory()->create(['name' => 'Bank Jepang']);
        Livewire::test(OperationsOverview::class)->assertSee('Perlu tindakan');
        Livewire::test(BatchOverview::class)->assertCanSeeTableRecords([$batch])->assertSee('3');
        Livewire::test(FinanceOverview::class)->assertSee('Bank Jepang')->assertSee('Pemasukan hari ini');
        $this->get(route('filament.admin.pages.dashboard'))->assertOk();
    }

    public function test_new_orders_and_payment_proofs_notify_only_active_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();
        $disabled = User::factory()->admin()->create(['is_active' => false]);
        ExchangeRate::factory()->create();
        $variant = ProductVariant::factory()->create();
        $order = app(PlaceOrder::class)->handle([
            'name' => 'Raka', 'phone' => '08012345678', 'postal_code' => '530-0001', 'prefecture' => 'Osaka',
            'city' => 'Osaka', 'address_line1' => 'Umeda 1',
        ], [['type' => 'product', 'name' => 'Case', 'qty' => 1, 'refs' => ['product_variant_id' => $variant->id]]], Currency::Jpy, OrderSource::Website);
        Notification::assertSentTo($admin, AdminOperationalNotification::class, fn ($notification): bool => $notification->subject === 'Order baru' && $notification->afterCommit === true);
        app(SubmitPaymentProof::class)->handle($order, UploadedFile::fake()->image('proof.jpg'), CashAccount::factory()->create());
        Notification::assertSentTo($admin, AdminOperationalNotification::class, fn ($notification): bool => $notification->subject === 'Bukti pembayaran baru');
        Notification::assertNotSentTo($staff, AdminOperationalNotification::class);
        Notification::assertNotSentTo($disabled, AdminOperationalNotification::class);
    }

    public function test_accepted_payment_notifies_admin_to_review_design(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->status(OrderStatus::MenungguVerifikasi)->create();
        $item = OrderItem::factory()->for($order)->create();
        OrderItemDesign::factory()->for($item)->create();
        $payment = Payment::factory()->for($order)->create();
        app(AcceptPayment::class)->handle($payment, $admin);
        Notification::assertSentTo($admin, AdminOperationalNotification::class, fn ($notification): bool => $notification->subject === 'Desain perlu review');
    }

    public function test_scheduled_alerts_are_deduplicated_and_ignore_recent_orders(): void
    {
        Cache::flush();
        $admin = User::factory()->admin()->create();
        Order::factory()->status(OrderStatus::MenungguVerifikasi)->create(['updated_at' => now()->subHours(13)]);
        Order::factory()->status(OrderStatus::MenungguVerifikasi)->create(['updated_at' => now()->subHour()]);
        PreorderBatch::factory()->create(['cutoff_at' => now()->addHours(23)]);
        $this->artisan('operations:send-alerts')->assertSuccessful();
        $this->artisan('operations:send-alerts')->assertSuccessful();
        Notification::assertSentToTimes($admin, AdminOperationalNotification::class, 2);
    }

    public function test_staff_can_record_a_complaint_through_the_form(): void
    {
        $this->actingAs(User::factory()->staff()->create());
        $order = Order::factory()->status(OrderStatus::Dikirim)->create(['shipped_at' => now()->subDay()]);
        Livewire::test(ManageOrderComplaints::class)->callAction('create', [
            'order_id' => $order->id, 'type' => 'cacat', 'description' => 'Casing retak saat dibuka',
            'received_at' => now('Asia/Tokyo')->toDateString(),
            'evidence_path' => UploadedFile::fake()->create('unboxing.mp4', 100, 'video/mp4'),
            'photo_path' => UploadedFile::fake()->image('retak.jpg'),
        ])->assertHasNoActionErrors();
        $complaint = $order->complaints()->sole();
        Storage::disk('local')->assertExists($complaint->evidence_path);
        Storage::disk('local')->assertExists($complaint->photo_path);
    }

    public function test_missing_evidence_does_not_create_a_complaint(): void
    {
        $order = Order::factory()->status(OrderStatus::Dikirim)->create(['shipped_at' => now()->subDay()]);
        try {
            app(RecordComplaint::class)->handle($order, User::factory()->staff()->create(), [
                'type' => 'cacat', 'description' => 'Retak',
                'received_at' => now('Asia/Tokyo')->toDateString(),
                'evidence_path' => 'complaints/missing.mp4', 'photo_path' => 'complaints/missing.jpg',
            ]);
            $this->fail('Missing evidence was accepted.');
        } catch (BusinessRuleException) {
            $this->assertDatabaseCount('order_complaints', 0);
        }
    }

    public function test_settings_recheck_permission_when_a_previously_authorized_user_is_demoted(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $component = Livewire::test(StoreSettings::class);
        $admin->update(['role' => UserRole::Staff]);
        $component->call('save')->assertForbidden();
        $this->assertDatabaseCount('store_setting_changes', 0);
    }

    public function test_changing_cutoff_day_does_not_reuse_an_existing_batch_code(): void
    {
        PreorderBatch::factory()->create([
            'code' => 'PO-2026-W40', 'status' => BatchStatus::Ditutup,
            'cutoff_at' => CarbonImmutable::parse('2026-10-01 18:00', 'Asia/Tokyo')->utc(),
        ]);
        Setting::updateOrCreate(['key' => 'batch_period'], ['value' => 'weekly']);
        Setting::updateOrCreate(['key' => 'batch_cutoff_day'], ['value' => 'friday']);
        Setting::updateOrCreate(['key' => 'batch_cutoff_time'], ['value' => '18:30']);
        $batch = app(EnsureOpenBatch::class)->handle();
        $this->assertSame('PO-2026-W41', $batch->code);
        $this->assertSame('2026-10-09 18:30', $batch->cutoff_at->timezone('Asia/Tokyo')->format('Y-m-d H:i'));
    }

    public function test_customers_cannot_load_operational_widgets_directly(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(OperationsOverview::class)->assertForbidden();
        Livewire::test(BatchOverview::class)->assertForbidden();
    }

    public function test_settings_ignore_injected_keys_outside_the_editable_schema(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(StoreSettings::class)
            ->set('data.base_currency', 'JPY')
            ->set('data.unexpected_key', 'injected')
            ->call('save')->assertHasNoFormErrors();
        $this->assertNull(Setting::valueOf('unexpected_key'));
        $this->assertSame('IDR', Setting::valueOf('base_currency', 'IDR'));
    }
}
