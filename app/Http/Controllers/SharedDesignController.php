<?php

namespace App\Http\Controllers;

use App\Actions\Batches\BuildBatchRecap;
use App\Actions\Orders\PlaceOrder;
use App\Models\OrderItemDesign;
use App\Models\PreorderBatch;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Design files behind expiring signed links, as written into the monthly Excel export, so the
 * links work for whoever holds the file (admin or the print vendor) without logging in.
 */
class SharedDesignController extends Controller
{
    public function design(OrderItemDesign $design): StreamedResponse
    {
        $disk = Storage::disk(PlaceOrder::DESIGN_DISK);

        abort_unless($design->original_path && $disk->exists($design->original_path), 404);

        $design->loadMissing('orderItem.order', 'caseColor');
        $name = implode('_', [$design->orderItem->order->code, Str::slug($design->phone_model_text), Str::slug((string) $design->caseColor?->name)]);

        return $disk->download($design->original_path, $name.'.'.pathinfo($design->original_path, PATHINFO_EXTENSION));
    }

    public function batch(PreorderBatch $batch, BuildBatchRecap $recap): BinaryFileResponse
    {
        return response()->download($recap->zip($batch), "desain-{$batch->code}.zip")->deleteFileAfterSend();
    }
}
