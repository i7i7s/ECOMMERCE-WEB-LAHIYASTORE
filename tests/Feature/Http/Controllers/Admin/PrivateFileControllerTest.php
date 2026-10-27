<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\OrderItemDesign;
use App\Models\Payment;
use App\Models\PreorderBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateFileControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::disk('local')->put('payment-proofs/LS-1/bukti.jpg', 'proof-bytes');
        $this->payment = Payment::factory()->create(['proof_path' => 'payment-proofs/LS-1/bukti.jpg']);
    }

    public function test_staff_can_open_a_payment_proof(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.files.payment-proof', $this->payment))
            ->assertOk()
            ->assertStreamedContent('proof-bytes');
    }

    public function test_customer_cannot_open_a_payment_proof(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.files.payment-proof', $this->payment))
            ->assertForbidden();
    }

    public function test_guest_is_sent_to_admin_login(): void
    {
        $this->get(route('admin.files.payment-proof', $this->payment))
            ->assertRedirect('/admin/login');
    }

    public function test_missing_design_file_returns_404(): void
    {
        $design = OrderItemDesign::factory()->create(['original_path' => 'designs/hilang.png']);

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.files.design', $design))
            ->assertNotFound();
    }

    public function test_staff_downloads_batch_recap_csv(): void
    {
        $batch = PreorderBatch::factory()->create(['code' => 'PO-2026-W40']);
        Order::factory()->paid($batch)->create();

        $this->actingAs(User::factory()->staff()->create())
            ->get(route('admin.files.batch-recap', ['batch' => $batch, 'format' => 'csv']))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="rekap-PO-2026-W40.csv"');
    }
}
