<?php

namespace Tests\Feature\Actions\Batches;

use App\Actions\Batches\BuildBatchRecap;
use App\Enums\OrderStatus;
use App\Models\CaseColor;
use App\Models\CaseType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemDesign;
use App\Models\PreorderBatch;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BuildBatchRecapTest extends TestCase
{
    use LazilyRefreshDatabase;

    private PreorderBatch $batch;

    private CaseType $softcase;

    private CaseColor $clear;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->batch = PreorderBatch::factory()->create(['code' => 'PO-2026-W40']);
        $this->softcase = CaseType::factory()->create(['name' => 'Softcase']);
        $this->clear = CaseColor::factory()->create(['name' => 'Bening']);
    }

    private function customLine(Order $order, string $model, int $qty = 1, ?string $file = null): void
    {
        OrderItemDesign::factory()
            ->for(OrderItem::factory()->customCase()->for($order)->state(['qty' => $qty]))
            ->for($this->softcase)
            ->for($this->clear, 'caseColor')
            ->create(['phone_model_text' => $model, 'original_path' => $file]);
    }

    public function test_summary_counts_cases_per_model_type_and_colour_excluding_cancelled_orders(): void
    {
        $first = Order::factory()->paid($this->batch)->create();
        $second = Order::factory()->paid($this->batch)->create();
        $cancelled = Order::factory()->for($this->batch, 'batch')->status(OrderStatus::Dibatalkan)->create();
        $this->customLine($first, 'iPhone 17 Pro Max', 2);
        $this->customLine($second, 'iPhone 17 Pro Max');
        $this->customLine($second, 'Google Pixel 9');
        $this->customLine($cancelled, 'iPhone 17 Pro Max', 5);

        $summary = app(BuildBatchRecap::class)->summary($this->batch);

        $this->assertSame([
            ['phone_model' => 'iPhone 17 Pro Max', 'case_type' => 'Softcase', 'color' => 'Bening', 'qty' => 3],
            ['phone_model' => 'Google Pixel 9', 'case_type' => 'Softcase', 'color' => 'Bening', 'qty' => 1],
        ], $summary->all());
    }

    public function test_files_are_named_with_order_code_item_number_model_type_and_colour(): void
    {
        $order = Order::factory()->paid($this->batch)->create(['code' => 'LS-20261001-001']);
        $this->customLine($order, 'iPhone 17 Pro Max', 1, 'designs/LS-20261001-001/1.png');
        $this->customLine($order, 'Google Pixel 9', 1, 'designs/LS-20261001-001/2.jpg');

        $lines = app(BuildBatchRecap::class)->lines($this->batch);

        $this->assertSame(
            ['LS-20261001-001_1_iphone-17-pro-max_softcase_bening.png', 'LS-20261001-001_2_google-pixel-9_softcase_bening.jpg'],
            $lines->pluck('file_name')->all(),
        );
    }

    public function test_csv_lists_every_line_for_the_vendor(): void
    {
        $order = Order::factory()->paid($this->batch)->create(['code' => 'LS-20261001-001']);
        $this->customLine($order, 'iPhone 17 Pro Max', 2);

        $rows = array_map('str_getcsv', array_filter(explode("\n", app(BuildBatchRecap::class)->csv($this->batch))));

        $this->assertSame(['kode_order', 'no_item', 'tipe_hp', 'jenis_case', 'warna', 'qty', 'nama_file', 'catatan_posisi'], $rows[0]);
        $this->assertSame(['LS-20261001-001', '1', 'iPhone 17 Pro Max', 'Softcase', 'Bening', '2', 'LS-20261001-001_1_iphone-17-pro-max_softcase_bening.png', ''], $rows[1]);
    }

    public function test_zip_holds_renamed_design_files_csv_and_pdf(): void
    {
        $order = Order::factory()->paid($this->batch)->create(['code' => 'LS-20261001-001']);
        Storage::disk('local')->put('designs/LS-20261001-001/1.png', 'image-bytes');
        $this->customLine($order, 'iPhone 17 Pro Max', 1, 'designs/LS-20261001-001/1.png');

        $path = app(BuildBatchRecap::class)->zip($this->batch);

        $zip = new ZipArchive;
        $zip->open($path);
        $this->assertSame('image-bytes', $zip->getFromName('desain/LS-20261001-001_1_iphone-17-pro-max_softcase_bening.png'));
        $this->assertNotFalse($zip->locateName('rekap.csv'));
        $this->assertStringStartsWith('%PDF', $zip->getFromName('rekap.pdf'));
        $zip->close();
        unlink($path);
    }
}
