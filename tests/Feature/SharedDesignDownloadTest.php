<?php

namespace Tests\Feature;

use App\Actions\Orders\PlaceOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemDesign;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SharedDesignDownloadTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function design(): OrderItemDesign
    {
        Storage::fake(PlaceOrder::DESIGN_DISK);
        $order = Order::factory()->paid()->create(['code' => 'LS-20260902-001']);
        $item = OrderItem::factory()->customCase()->for($order)->create();
        $design = OrderItemDesign::factory()->for($item)->create(['original_path' => 'designs/LS-20260902-001/1.png', 'phone_model_text' => 'iPhone 17 Pro']);
        Storage::disk(PlaceOrder::DESIGN_DISK)->put($design->original_path, 'png-bytes');

        return $design;
    }

    public function test_signed_link_downloads_the_design_without_logging_in(): void
    {
        $design = $this->design();

        $this->get(URL::temporarySignedRoute('shared.design', now()->addDay(), ['design' => $design->id]))
            ->assertOk()
            ->assertDownload('LS-20260902-001_iphone-17-pro_'.str($design->caseColor->name)->slug().'.png');
    }

    public function test_unsigned_or_expired_link_is_refused(): void
    {
        $design = $this->design();

        $this->get(route('shared.design', ['design' => $design->id]))->assertForbidden();
        $this->get(URL::temporarySignedRoute('shared.design', now()->subMinute(), ['design' => $design->id]))->assertForbidden();
    }

    public function test_signed_link_downloads_all_designs_of_a_batch_as_zip(): void
    {
        $batch = $this->design()->orderItem->order->batch;

        $this->get(URL::temporarySignedRoute('shared.batch-designs', now()->addDay(), ['batch' => $batch->id]))
            ->assertOk()
            ->assertDownload("desain-{$batch->code}.zip");
    }
}
