<?php

namespace App\Actions\Batches;

use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\PreorderBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Production recap for the vendor (BATCH-04, PRD §6.9): quantities per phone model ×
 * case type × colour, rekap.csv, a PDF summary and a ZIP with every design file named
 * {order_code}_{item_no}_{phone-model}_{case-type}_{colour}.
 */
class BuildBatchRecap
{
    /**
     * One row per custom case line, in order.
     *
     * @return Collection<int, array{order_code: string, item_no: int, phone_model: string, case_type: string, color: string, qty: int, file_name: string, original_path: string|null, has_preview: bool, position_note: string|null}>
     */
    public function lines(PreorderBatch $batch): Collection
    {
        return OrderItem::query()
            ->where('item_type', OrderItemType::CustomCase)
            ->whereHas('order', fn ($query) => $query->whereBelongsTo($batch, 'batch')->where('status', '!=', OrderStatus::Dibatalkan))
            ->with(['order:id,code', 'design.caseType', 'design.caseColor'])
            ->orderBy('order_id')
            ->orderBy('id')
            ->get()
            ->groupBy('order_id')
            ->flatMap(fn (Collection $items) => $items->values()->map(function (OrderItem $item, int $index): array {
                $design = $item->design;
                $extension = $design->original_path ? pathinfo($design->original_path, PATHINFO_EXTENSION) : 'png';

                return [
                    'order_code' => $item->order->code,
                    'item_no' => $index + 1,
                    'phone_model' => $design->phone_model_text,
                    'case_type' => $design->caseType->name,
                    'color' => $design->caseColor->name,
                    'qty' => $item->qty,
                    'file_name' => implode('_', [$item->order->code, $index + 1, Str::slug($design->phone_model_text), Str::slug($design->caseType->name), Str::slug($design->caseColor->name)]).'.'.$extension,
                    'original_path' => $design->original_path,
                    'has_preview' => $design->has_preview,
                    'position_note' => $design->position_note,
                ];
            }))
            ->values();
    }

    /**
     * Quantity per phone model × case type × colour, most ordered first.
     *
     * @return Collection<int, array{phone_model: string, case_type: string, color: string, qty: int}>
     */
    public function summary(PreorderBatch $batch): Collection
    {
        return $this->lines($batch)
            ->groupBy(fn (array $line): string => "{$line['phone_model']}|{$line['case_type']}|{$line['color']}")
            ->map(fn (Collection $group): array => [
                'phone_model' => $group->first()['phone_model'],
                'case_type' => $group->first()['case_type'],
                'color' => $group->first()['color'],
                'qty' => $group->sum('qty'),
            ])
            ->sortByDesc('qty')
            ->values();
    }

    public function csv(PreorderBatch $batch): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['kode_order', 'no_item', 'tipe_hp', 'jenis_case', 'warna', 'qty', 'nama_file', 'catatan_posisi']);

        foreach ($this->lines($batch) as $line) {
            fputcsv($handle, [$line['order_code'], $line['item_no'], $line['phone_model'], $line['case_type'], $line['color'], $line['qty'], $line['file_name'], $line['position_note']]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public function pdf(PreorderBatch $batch): string
    {
        return Pdf::loadView('pdf.batch-recap', [
            'batch' => $batch,
            'summary' => $this->summary($batch),
            'lines' => $this->lines($batch),
        ])->output();
    }

    /**
     * Builds the ZIP in a temporary file and returns its path. Lines whose design file
     * is missing are listed in rekap.csv but have no file in the archive.
     */
    public function zip(PreorderBatch $batch): string
    {
        $path = tempnam(sys_get_temp_dir(), 'rekap-').'.zip';
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('ZIP rekap tidak bisa dibuat.');
        }

        $disk = Storage::disk(PlaceOrder::DESIGN_DISK);

        foreach ($this->lines($batch) as $line) {
            if ($line['original_path'] && $disk->exists($line['original_path'])) {
                $zip->addFile($disk->path($line['original_path']), 'desain/'.$line['file_name']);
            }
        }

        $zip->addFromString('rekap.csv', $this->csv($batch));
        $zip->addFromString('rekap.pdf', $this->pdf($batch));
        $zip->close();

        return $path;
    }
}
