<?php

namespace Tests\Feature\Livewire\Store;

use App\Enums\Currency;
use App\Enums\OrderStatus;
use App\Livewire\Store\PolicyDetail;
use App\Models\CashAccount;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PolicyDetailTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function topicPages(): array
    {
        return [
            'preorder' => ['preorder', 'Satu pesanan. Enam tahap.'],
            'payment' => ['pembayaran', 'Rekening sesuai mata uang.'],
            'cancellation' => ['pembatalan', 'Apa pilihan untuk pesananmu?'],
            'complaints' => ['retur-refund', 'Masih bisa mengajukan komplain?'],
        ];
    }

    #[DataProvider('topicPages')]
    public function test_topic_has_its_own_page_and_navigation(string $slug, string $heading): void
    {
        $this->get(route('policy.show', ['topic' => $slug]))->assertOk()
            ->assertSee($heading)->assertSee('Kebijakan toko')->assertSee('Pertanyaan yang sering muncul.')
            ->assertSee(route('policy'), false);
        $this->get(route('policy'))->assertSee(route('policy.show', ['topic' => $slug]), false);
    }

    public function test_unknown_topic_returns_not_found(): void
    {
        $this->get('/kebijakan/tidak-ada')->assertNotFound();
    }

    public function test_custom_policy_is_shared_and_escaped_on_both_pages(): void
    {
        Setting::factory()->create(['key' => 'policy_payment', 'value' => 'Kebijakan pembayaran khusus <script>alert(1)</script>']);
        $this->get(route('policy'))->assertSee('Kebijakan pembayaran khusus')->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('policy.show', ['topic' => 'pembayaran']))->assertSee('Kebijakan pembayaran khusus')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_payment_guide_uses_current_settings_and_only_checkout_accounts(): void
    {
        Setting::factory()->create(['key' => 'payment_window_hours', 'value' => '48']);
        Setting::factory()->create(['key' => 'max_proof_upload_mb', 'value' => '2']);
        CashAccount::factory()->create(['currency' => Currency::Jpy, 'show_at_checkout' => true, 'bank_name' => 'Bank Tokyo Test', 'account_number' => '1234567']);
        CashAccount::factory()->create(['currency' => Currency::Idr, 'show_at_checkout' => true, 'bank_name' => 'BCA Guide']);
        CashAccount::factory()->create(['currency' => Currency::Jpy, 'show_at_checkout' => true, 'is_active' => false, 'bank_name' => 'Hidden Bank']);
        $this->get(route('policy.show', ['topic' => 'pembayaran']))->assertOk()
            ->assertSee('Bank Tokyo Test')->assertSee('BCA Guide')->assertSee('48 jam')->assertSee('maksimal 2 MB')
            ->assertDontSee('Hidden Bank');
    }

    public function test_cancellation_explanations_follow_the_real_status_transitions(): void
    {
        Livewire::test(PolicyDetail::class, ['topic' => 'pembatalan'])
            ->assertViewHas('guideState', function (array $state): bool {
                $statuses = $state['statuses'];

                return $statuses[OrderStatus::Dibayar->value]['allowed']
                    && $statuses[OrderStatus::MenungguVerifikasi->value]['allowed']
                    && ! $statuses[OrderStatus::Diproduksi->value]['allowed']
                    && ! $statuses[OrderStatus::Dikirim->value]['allowed'];
            });
    }

    /** @return array<string, array{string, string, string}> */
    public static function complaintDates(): array
    {
        return [
            'within both limits' => ['2026-10-01', '2026-10-08', 'Masih dalam batas pengajuan.'],
            'last received day inclusive' => ['2026-10-01', '2026-10-07', 'Masih dalam batas pengajuan.'],
            'last shipped day inclusive' => ['2026-09-30', '2026-10-09', 'Masih dalam batas pengajuan.'],
            'received deadline exceeded' => ['2026-10-01', '2026-10-06', 'Batas waktu sudah lewat.'],
            'shipping deadline exceeded' => ['2026-09-28', '2026-10-09', 'Batas waktu sudah lewat.'],
            'received before shipped' => ['2026-10-09', '2026-10-08', 'Urutan tanggal belum sesuai.'],
            'future date' => ['2026-10-09', '2026-10-11', 'Urutan tanggal belum sesuai.'],
            'invalid calendar date' => ['2026-02-31', '2026-10-09', 'Periksa kembali tanggalnya.'],
        ];
    }

    #[DataProvider('complaintDates')]
    public function test_complaint_window_handles_both_deadlines_and_invalid_dates(string $shipped, string $received, string $heading): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 12:00', 'Asia/Tokyo'));
        Livewire::test(PolicyDetail::class, ['topic' => 'retur-refund'])
            ->set('shippedDate', $shipped)->set('receivedDate', $received)->assertSee($heading);
    }

    public function test_complaint_checker_respects_changed_settings_and_does_not_create_orders(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-10 12:00', 'Asia/Tokyo'));
        Setting::factory()->create(['key' => 'complaint_days_after_received', 'value' => '1']);
        Livewire::test(PolicyDetail::class, ['topic' => 'retur-refund'])
            ->set('shippedDate', '2026-10-01')->set('receivedDate', '2026-10-08')->assertSee('Batas waktu sudah lewat.');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_complaints', 0);
    }
}
