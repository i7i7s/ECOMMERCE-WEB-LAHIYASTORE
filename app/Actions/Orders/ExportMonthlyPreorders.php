<?php

namespace App\Actions\Orders;

use App\Enums\Currency;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PreorderBatch;
use App\Support\BaseCurrency;
use App\Support\BatchSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Excel workbook of the preorders placed in one month (Tokyo time): a summary sheet, one row
 * per order and one row per item. Design files are linked through expiring signed URLs, so the
 * workbook can be handed to the print vendor. Written to a temporary file the caller sends and deletes.
 */
class ExportMonthlyPreorders
{
    /** Days the design links in the workbook keep working. */
    public const LINK_DAYS = 30;

    private const INK = '1D1D1F';

    private const MUTED = '6E6E73';

    private const LINE = 'E8E8ED';

    private const ZEBRA = 'FAFAFC';

    private const LINK = '0066CC';

    private const DATE_FORMAT = 'd mmm yyyy hh:mm';

    private Writer $writer;

    private CarbonImmutable $linksExpireAt;

    /**
     * @return array{path: string, filename: string, orders: int}
     */
    public function handle(CarbonImmutable $month): array
    {
        $from = $month->setTimezone(BatchSchedule::TIMEZONE)->startOfMonth();
        $until = $from->endOfMonth();

        /** @var Collection<int, Order> $orders */
        $orders = Order::query()
            ->with(['batch', 'items.design.caseType', 'items.design.caseColor'])
            ->whereBetween('created_at', [$from->utc(), $until->utc()])
            ->orderBy('created_at')
            ->get();

        $this->linksExpireAt = CarbonImmutable::now(BatchSchedule::TIMEZONE)->addDays(self::LINK_DAYS);
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'preorder-'.Str::uuid().'.xlsx';
        $this->writer = new Writer;
        $this->writer->setCreator('Lahiya Store');
        $this->writer->openToFile($path);

        $this->summarySheet($orders, $from);
        $this->ordersSheet($orders);
        $this->itemsSheet($orders);

        $this->writer->close();

        return ['path' => $path, 'filename' => 'preorder-'.$from->format('Y-m').'.xlsx', 'orders' => $orders->count()];
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function summarySheet(Collection $orders, CarbonImmutable $from): void
    {
        $sheet = $this->writer->getCurrentSheet();
        $sheet->setName('Ringkasan');
        $sheet->setSheetView((new SheetView)->setShowGridLines(false));
        $sheet->setColumnWidth(36, 1);
        $sheet->setColumnWidth(18, 2, 3, 4);
        $sheet->setColumnWidth(34, 5);

        $active = $orders->where('status', '!=', OrderStatus::Dibatalkan);
        $paid = $active->whereNotNull('paid_at');
        $banner = (new Style)->setBackgroundColor(self::INK)->setFontColor('FFFFFF')->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

        $this->add(Row::fromValues(['lahiya store'], (clone $banner)->setFontBold()->setFontSize(22))->setHeight(40), merge: 5);
        $this->add(Row::fromValues(['Rekap preorder '.$from->locale('id')->translatedFormat('F Y')], (clone $banner)->setFontSize(13)->setFontColor('F5F5F7'))->setHeight(24), merge: 5);
        $this->add(Row::fromValues([
            'Diekspor '.now(BatchSchedule::TIMEZONE)->locale('id')->translatedFormat('j M Y H:i').' JST · batch '.($orders->pluck('batch.code')->filter()->unique()->implode(', ') ?: '-'),
        ], (clone $banner)->setFontSize(10)->setFontColor('A1A1A6'))->setHeight(22), merge: 5);
        $this->add(Row::fromValues([]));

        $kpiLabel = (new Style)->setFontSize(10)->setFontColor(self::MUTED)->setBackgroundColor('F5F5F7');
        $kpiValue = (new Style)->setFontSize(20)->setFontBold()->setFontColor(self::INK)->setBackgroundColor('F5F5F7')->setCellAlignment(CellAlignment::LEFT);
        $this->add(Row::fromValues(['Total order', 'Sudah dibayar', 'Dibatalkan', 'Qty item', 'Pelanggan'], $kpiLabel)->setHeight(20));
        $this->add(Row::fromValues([
            $orders->count(),
            $paid->count(),
            $orders->count() - $active->count(),
            (int) $active->flatMap->items->sum('qty'),
            $orders->pluck('customer_phone')->unique()->count(),
        ], $kpiValue)->setHeight(34));
        $this->add(Row::fromValues([]));

        $this->section('Status order');
        $this->tableHeader(['Status', 'Jumlah order', 'Porsi', 'Grafik']);

        foreach (OrderStatus::cases() as $status) {
            $count = $orders->where('status', $status)->count();
            $share = $orders->isEmpty() ? 0 : $count / $orders->count();
            [$text, $fill] = $this->statusColors($status);

            $this->add(new Row([
                Cell::fromValue($status->label(), $this->statusStyle($status)),
                Cell::fromValue($count, $count === 0 ? (new Style)->setFontColor('A1A1A6') : null),
                Cell::fromValue($share, (new Style)->setFormat('0%')->setFontColor(self::MUTED)),
                Cell::fromValue(str_repeat('█', (int) round($share * 20)), (new Style)->setFontColor($fill === 'F5F5F7' ? 'A1A1A6' : $text)),
            ], $this->bodyStyle()));
        }

        $this->add(Row::fromValues(['Total order', $orders->count()], $this->totalStyle()));
        $this->add(Row::fromValues([]));

        $this->section('Pendapatan (order dibayar, tidak dibatalkan)');
        $this->tableHeader(['Mata uang', 'Order', 'Total']);

        foreach (Currency::cases() as $currency) {
            $inCurrency = $paid->where('currency', $currency);
            $this->add(new Row([
                Cell::fromValue("Dibayar dalam {$currency->value}"),
                Cell::fromValue($inCurrency->count()),
                Cell::fromValue((int) $inCurrency->sum('total'), $this->moneyStyle($currency)),
            ], $this->bodyStyle()));
        }

        $base = BaseCurrency::currency();
        $this->add(new Row([
            Cell::fromValue("Total dalam {$base->value} (kurs saat order)"),
            Cell::fromValue($paid->count()),
            Cell::fromValue((int) $paid->sum(fn (Order $order): int => BaseCurrency::convert($order->total, $order->currency, (float) $order->exchange_rate)), $this->moneyStyle($base)),
        ], $this->totalStyle()));
        $this->add(Row::fromValues([]));

        $this->section('Paling banyak dipesan');
        $this->tableHeader(['Item', 'Qty', 'Order']);

        $active->flatMap(fn (Order $order) => $order->items->map(fn (OrderItem $item): array => ['label' => $this->itemLabel($item), 'qty' => $item->qty, 'order' => $order->id]))
            ->groupBy('label')
            ->map(fn (Collection $rows, string $label): array => [$label, (int) $rows->sum('qty'), $rows->pluck('order')->unique()->count()])
            ->sortByDesc(1)
            ->take(5)
            ->each(fn (array $row) => $this->add(Row::fromValues($row, $this->bodyStyle())));

        $batches = $active->filter(fn (Order $order): bool => $order->batch !== null && $order->items->contains('item_type', OrderItemType::CustomCase))
            ->pluck('batch')
            ->unique('id');

        if ($batches->isNotEmpty()) {
            $this->add(Row::fromValues([]));
            $this->section('File desain casing');
            $this->tableHeader(['Batch', 'Status batch', 'Desain', 'Unduh']);

            foreach ($batches as $batch) {
                /** @var PreorderBatch $batch */
                $designs = $active->where('preorder_batch_id', $batch->id)->flatMap->items->where('item_type', OrderItemType::CustomCase)->count();

                $this->add(new Row([
                    Cell::fromValue($batch->code, (new Style)->setFontBold()),
                    Cell::fromValue($batch->status->getLabel()),
                    Cell::fromValue($designs),
                    $this->link(URL::temporarySignedRoute('shared.batch-designs', $this->linksExpireAt, ['batch' => $batch->id]), 'Unduh semua desain (ZIP)'),
                ], $this->bodyStyle()));
            }

            $this->add(Row::fromValues([
                'Link berlaku sampai '.$this->linksExpireAt->locale('id')->translatedFormat('j F Y').'. Siapa pun yang memegang file ini bisa membuka link-nya, jadi bagikan hanya ke vendor cetak.',
            ], (new Style)->setFontItalic()->setFontSize(9)->setFontColor(self::MUTED)), merge: 5);
        }
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function ordersSheet(Collection $orders): void
    {
        $sheet = $this->writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Order');
        $sheet->setSheetView((new SheetView)->setFreezeRow(2)->setFreezeColumn('B'));
        $sheet->setColumnWidth(18, 1);
        $sheet->setColumnWidth(17, 2, 12);
        $sheet->setColumnWidth(20, 3, 8);
        $sheet->setColumnWidth(14, 4, 5, 9, 13, 14);
        $sheet->setColumnWidth(58, 6);
        $sheet->setColumnWidth(11, 7, 10);
        $sheet->setColumnWidth(13, 11);
        $sheet->setColumnWidth(26, 15);

        $columns = [
            'Kode', 'Tanggal (JST)', 'Pelanggan', 'No. HP', 'Kode pos', 'Alamat', 'Sumber', 'Status', 'Batch',
            'Mata uang', 'Total', 'Dibayar (JST)', 'Kurir', 'Resi', 'Alasan batal',
        ];
        $this->tableHeader($columns, height: 28);

        foreach ($orders->values() as $index => $order) {
            $this->add(new Row([
                Cell::fromValue($order->code, (new Style)->setFontBold()),
                $this->date($order->created_at),
                Cell::fromValue($order->customer_name),
                Cell::fromValue((string) $order->customer_phone),
                Cell::fromValue((string) $order->postal_code),
                Cell::fromValue(collect([$order->prefecture, $order->city, $order->address_line1, $order->address_line2])->filter()->implode(', ')),
                Cell::fromValue($order->source->getLabel()),
                Cell::fromValue($order->status->label(), $this->statusStyle($order->status)),
                Cell::fromValue((string) $order->batch?->code),
                Cell::fromValue($order->currency->value, (new Style)->setCellAlignment(CellAlignment::CENTER)),
                Cell::fromValue($order->total, $this->moneyStyle($order->currency)->setFontBold()),
                $this->date($order->paid_at),
                Cell::fromValue((string) $order->domestic_courier),
                Cell::fromValue((string) $order->tracking_number),
                Cell::fromValue((string) $order->cancel_reason, (new Style)->setFontColor('C0322B')),
            ], $this->bodyStyle($index)));
        }

        $sheet->setAutoFilter(new AutoFilter(0, 1, count($columns) - 1, max(2, $orders->count() + 1)));
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function itemsSheet(Collection $orders): void
    {
        $sheet = $this->writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Item');
        $sheet->setSheetView((new SheetView)->setFreezeRow(2)->setFreezeColumn('B'));
        $sheet->setColumnWidth(18, 1);
        $sheet->setColumnWidth(17, 2, 8);
        $sheet->setColumnWidth(54, 3);
        $sheet->setColumnWidth(26, 4);
        $sheet->setColumnWidth(13, 5, 6, 7, 11, 12);
        $sheet->setColumnWidth(8, 9);
        $sheet->setColumnWidth(11, 10);
        $sheet->setColumnWidth(36, 13);
        $sheet->setColumnWidth(18, 14);

        $columns = [
            'Kode order', 'Status order', 'Item', 'Tipe HP', 'Jenis case', 'Warna case', 'Preview', 'Review desain',
            'Qty', 'Mata uang', 'Harga satuan', 'Subtotal', 'Catatan posisi', 'File desain',
        ];
        $this->tableHeader($columns, height: 28);
        $row = 0;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                /** @var OrderItem $item */
                $design = $item->design;

                $this->add(new Row([
                    Cell::fromValue($order->code, (new Style)->setFontBold()),
                    Cell::fromValue($order->status->label(), $this->statusStyle($order->status)),
                    Cell::fromValue($item->item_name),
                    Cell::fromValue((string) $design?->phone_model_text),
                    Cell::fromValue((string) $design?->caseType?->name),
                    Cell::fromValue((string) $design?->caseColor?->name),
                    Cell::fromValue($design === null ? '' : ($design->has_preview ? 'Ya' : 'Perkiraan')),
                    Cell::fromValue((string) $design?->review_status?->getLabel()),
                    Cell::fromValue($item->qty, (new Style)->setCellAlignment(CellAlignment::CENTER)->setFontBold()),
                    Cell::fromValue($order->currency->value, (new Style)->setCellAlignment(CellAlignment::CENTER)),
                    Cell::fromValue($item->unit_price, $this->moneyStyle($order->currency)),
                    Cell::fromValue($item->subtotal, $this->moneyStyle($order->currency)->setFontBold()),
                    Cell::fromValue((string) $design?->position_note),
                    $design?->original_path
                        ? $this->link(URL::temporarySignedRoute('shared.design', $this->linksExpireAt, ['design' => $design->id]), 'Buka desain')
                        : Cell::fromValue(''),
                ], $this->bodyStyle($row++)));
            }
        }

        $sheet->setAutoFilter(new AutoFilter(0, 1, count($columns) - 1, max(2, $row + 1)));
    }

    /**
     * Writes a row, optionally merging its first cell across the first $merge columns.
     */
    private function add(Row $row, int $merge = 0): void
    {
        $this->writer->addRow($row);

        if ($merge > 1) {
            $sheet = $this->writer->getCurrentSheet();
            $line = $sheet->getWrittenRowCount();
            $this->writer->getOptions()->mergeCells(0, $line, $merge - 1, $line, $sheet->getIndex());
        }
    }

    private function section(string $title): void
    {
        $this->add(Row::fromValues([$title], (new Style)->setFontBold()->setFontSize(13)->setFontColor(self::INK))->setHeight(24));
    }

    /**
     * @param  list<string>  $columns
     */
    private function tableHeader(array $columns, float $height = 22): void
    {
        $style = (new Style)
            ->setFontBold()
            ->setFontColor('FFFFFF')
            ->setBackgroundColor(self::INK)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);

        $this->add(Row::fromValues($columns, $style)->setHeight($height));
    }

    private function bodyStyle(int $index = 0): Style
    {
        $style = (new Style)
            ->setFontColor(self::INK)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setBorder(new Border(new BorderPart(Border::BOTTOM, self::LINE, Border::WIDTH_THIN)));

        return $index % 2 === 1 ? $style->setBackgroundColor(self::ZEBRA) : $style;
    }

    private function totalStyle(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontColor(self::INK)
            ->setBorder(new Border(new BorderPart(Border::TOP, self::INK, Border::WIDTH_THIN)));
    }

    private function moneyStyle(Currency $currency): Style
    {
        return (new Style)->setFormat($currency === Currency::Jpy ? '"¥"#,##0' : '"Rp"#,##0');
    }

    private function statusStyle(OrderStatus $status): Style
    {
        [$text, $fill] = $this->statusColors($status);

        return (new Style)->setFontBold()->setFontColor($text)->setBackgroundColor($fill);
    }

    /**
     * Text and fill colour per status, taken from the storefront's status palette.
     *
     * @return array{string, string}
     */
    private function statusColors(OrderStatus $status): array
    {
        return match ($status) {
            OrderStatus::MenungguKonfirmasi, OrderStatus::MenungguPembayaran, OrderStatus::MenungguVerifikasi => ['8A5A00', 'FFF4D6'],
            OrderStatus::Dibayar, OrderStatus::Diproduksi => ['6B3FB5', 'F1EAFB'],
            OrderStatus::DikirimKeJepang, OrderStatus::TibaDiJepang, OrderStatus::Dikirim => ['0B4A99', 'E8F0FC'],
            OrderStatus::Selesai => ['1A7F37', 'E8F5EC'],
            OrderStatus::Dibatalkan => ['C0322B', 'FDECEB'],
        };
    }

    private function date(?\DateTimeInterface $moment): Cell
    {
        return $moment === null
            ? Cell::fromValue('')
            : Cell::fromValue(CarbonImmutable::instance($moment)->setTimezone(BatchSchedule::TIMEZONE)->toDateTimeImmutable(), (new Style)->setFormat(self::DATE_FORMAT));
    }

    private function link(string $url, string $label): Cell
    {
        return Cell::fromValue('=HYPERLINK("'.$url.'","'.$label.'")', (new Style)->setFontColor(self::LINK)->setFontUnderline());
    }

    /**
     * Groups items for the "most ordered" table: the phone model for custom cases, the product otherwise.
     */
    private function itemLabel(OrderItem $item): string
    {
        return $item->design !== null
            ? "Casing {$item->design->phone_model_text}"
            : Str::before($item->item_name, ' · ');
    }
}
