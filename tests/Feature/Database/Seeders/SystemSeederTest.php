<?php

namespace Tests\Feature\Database\Seeders;

use App\Enums\BatchStatus;
use App\Models\PreorderBatch;
use App\Models\Setting;
use App\Models\TransactionCategory;
use Carbon\CarbonImmutable;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SystemSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creates_system_categories_and_one_open_batch(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-01 05:00', 'UTC'));

        $this->seed(SystemSeeder::class);

        $this->assertSame(
            ['bea_masuk', 'belanja_vendor', 'biaya_gateway', 'kredit_hp', 'ongkir_domestik', 'ongkir_internasional', 'penjualan', 'refund', 'transfer_keluar', 'transfer_masuk'],
            TransactionCategory::where('is_system', true)->orderBy('slug')->pluck('slug')->all(),
        );

        $batch = PreorderBatch::sole();
        // Monthly preorder: the batch closes on the last day of the month.
        $this->assertSame('PO-2026-10', $batch->code);
        $this->assertSame(BatchStatus::Dibuka, $batch->status);
        $this->assertSame('2026-10-31 23:59', $batch->cutoff_at->setTimezone('Asia/Tokyo')->format('Y-m-d H:i'));
    }

    public function test_reseeding_keeps_admin_changes_and_does_not_duplicate(): void
    {
        $this->seed(SystemSeeder::class);
        Setting::whereKey('payment_window_hours')->update(['value' => '48']);

        $this->seed(SystemSeeder::class);

        $this->assertSame('48', Setting::valueOf('payment_window_hours'));
        $this->assertSame(1, PreorderBatch::count());
        $this->assertSame(10, TransactionCategory::count());
    }
}
