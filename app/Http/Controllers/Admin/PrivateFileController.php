<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Batches\BuildBatchRecap;
use App\Actions\Orders\PlaceOrder;
use App\Actions\Payments\SubmitPaymentProof;
use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\Order;
use App\Models\OrderComplaint;
use App\Models\OrderItemDesign;
use App\Models\Payment;
use App\Models\PreorderBatch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves private files (payment proofs, original designs) to admin and staff only (PRD §9).
 */
class PrivateFileController extends Controller
{
    public function paymentProof(Payment $payment): StreamedResponse
    {
        Gate::authorize('view', $payment);

        return $this->stream(SubmitPaymentProof::PROOF_DISK, $payment->proof_path);
    }

    public function design(OrderItemDesign $design): StreamedResponse
    {
        Gate::authorize('view', $design);

        return $this->stream(PlaceOrder::DESIGN_DISK, $design->original_path);
    }

    public function cashAttachment(CashTransaction $transaction): StreamedResponse
    {
        Gate::authorize('view', $transaction);

        return $this->stream('local', $transaction->attachment_path);
    }

    public function packingPhoto(Order $order): StreamedResponse
    {
        Gate::authorize('view', $order);

        return $this->stream('local', $order->packing_photo_path);
    }

    /**
     * Printable slip that goes inside the parcel: order code, recipient and items with
     * design thumbnails (PRD §6.10 step 4).
     */
    public function packingSlip(Order $order): View
    {
        Gate::authorize('view', $order);

        return view('admin.packing-slip', [
            'order' => $order->load(['items.design.caseType', 'items.design.caseColor']),
        ]);
    }

    /**
     * Production recap for the vendor: zip (designs + csv + pdf), csv or pdf (BATCH-04).
     */
    public function batchRecap(PreorderBatch $batch, string $format, BuildBatchRecap $recap): Response|BinaryFileResponse
    {
        Gate::authorize('view', $batch);

        $name = "rekap-{$batch->code}";

        return match ($format) {
            'csv' => response($recap->csv($batch), headers: ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename=\"{$name}.csv\""]),
            'pdf' => response($recap->pdf($batch), headers: ['Content-Type' => 'application/pdf', 'Content-Disposition' => "attachment; filename=\"{$name}.pdf\""]),
            'zip' => response()->download($recap->zip($batch), "{$name}.zip")->deleteFileAfterSend(),
            default => abort(404),
        };
    }

    public function complaint(OrderComplaint $complaint, string $kind): StreamedResponse
    {
        Gate::authorize('view', $complaint);

        return $this->stream('local', $kind === 'video' ? $complaint->evidence_path : $complaint->photo_path);
    }

    private function stream(string $disk, ?string $path): StreamedResponse
    {
        abort_if($path === null || ! Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, headers: ['Cache-Control' => 'private, max-age=600']);
    }
}
