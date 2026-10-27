<?php

namespace Tests\Feature\Livewire\Store;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Store\PaymentPanel;
use App\Models\CashAccount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentPanelTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function trackedUnpaidOrder(array $attributes = []): Order
    {
        $order = Order::factory()->create($attributes);
        session()->put('tracked_orders', [$order->code]);

        return $order;
    }

    public function test_customer_pays_later_by_uploading_proof_for_a_tracked_order(): void
    {
        Storage::fake('local');
        $account = CashAccount::factory()->create(['bank_name' => 'Yucho', 'account_number' => '12345678']);
        $order = $this->trackedUnpaidOrder();

        Livewire::test(PaymentPanel::class, ['orderCode' => $order->code])
            ->assertSee('Yucho')
            ->assertSee('12345678')
            ->set('proof', UploadedFile::fake()->image('bukti.jpg'))
            ->call('uploadProof')
            ->assertHasNoErrors()
            ->assertSet('proofUploaded', true)
            ->assertDispatched('payment-submitted')
            ->assertSee('Bukti transfer terkirim');

        $payment = $order->payments()->sole();
        $this->assertSame(OrderStatus::MenungguVerifikasi, $order->refresh()->status);
        $this->assertTrue($payment->cashAccount->is($account));
        Storage::disk('local')->assertExists($payment->proof_path);
    }

    public function test_someone_who_did_not_verify_the_order_cannot_open_the_panel(): void
    {
        $order = Order::factory()->create();

        Livewire::test(PaymentPanel::class, ['orderCode' => $order->code])->assertNotFound();
    }

    public function test_signed_in_owner_can_pay_without_verifying_the_phone_number(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $customer->id]);
        CashAccount::factory()->create();

        $this->actingAs($customer);

        Livewire::test(PaymentPanel::class, ['orderCode' => $order->code])
            ->assertSee('Bukti transfer')
            ->assertSee('Bayar sebelum');
    }

    public function test_rejected_proof_reason_is_shown_so_the_customer_can_upload_again(): void
    {
        CashAccount::factory()->create();
        $order = $this->trackedUnpaidOrder();
        Payment::factory()->for($order)->create(['status' => PaymentStatus::Ditolak, 'reject_reason' => 'Nominal kurang ¥200']);

        Livewire::test(PaymentPanel::class, ['orderCode' => $order->code])
            ->assertSee('Bukti sebelumnya ditolak:')
            ->assertSee('Nominal kurang ¥200')
            ->assertSee('Kirim bukti');
    }

    public function test_overdue_order_cannot_take_a_proof_any_more(): void
    {
        Storage::fake('local');
        CashAccount::factory()->create();
        $order = $this->trackedUnpaidOrder(['payment_due_at' => now()->subMinute()]);

        Livewire::test(PaymentPanel::class, ['orderCode' => $order->code])
            ->assertSee('sudah lewat')
            ->assertDontSee('Kirim bukti')
            ->set('proof', UploadedFile::fake()->image('bukti.jpg'))
            ->call('uploadProof')
            ->assertHasErrors('proof');

        $this->assertSame(0, $order->payments()->count());
    }

    public function test_proof_must_be_an_image_or_pdf(): void
    {
        CashAccount::factory()->create();
        $order = $this->trackedUnpaidOrder();

        Livewire::test(PaymentPanel::class, ['orderCode' => $order->code])
            ->set('proof', UploadedFile::fake()->create('bukti.exe', 10))
            ->call('uploadProof')
            ->assertHasErrors(['proof' => 'mimes'])
            ->assertSee('Bukti harus berupa JPG, PNG, atau PDF.');
    }

    public function test_tracking_page_offers_payment_only_while_waiting_for_payment(): void
    {
        CashAccount::factory()->create();
        $unpaid = Order::factory()->create();
        $paid = Order::factory()->paid()->create();

        $this->withSession(['tracked_orders' => [$unpaid->code, $paid->code]]);

        $this->get(route('track', ['kode' => $unpaid->code]))->assertOk()->assertSee('Bayar pesanan');
        $this->get(route('track', ['kode' => $paid->code]))->assertOk()->assertDontSee('Bayar pesanan');
    }
}
