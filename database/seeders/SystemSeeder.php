<?php

namespace Database\Seeders;

use App\Enums\BatchStatus;
use App\Enums\CashAccountType;
use App\Enums\CashFlow;
use App\Enums\CategoryScope;
use App\Enums\Currency;
use App\Models\CaseColor;
use App\Models\CaseType;
use App\Models\CashAccount;
use App\Models\PreorderBatch;
use App\Models\Setting;
use App\Models\TransactionCategory;
use App\Support\BatchSchedule;
use Illuminate\Database\Seeder;

/**
 * Data every environment needs: built-in cash categories, default settings,
 * case types and colours, cash accounts and the first open batch. Safe to re-run.
 */
class SystemSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTransactionCategories();
        $this->seedSettings();
        $this->seedCaseOptions();
        $this->seedCashAccounts();
        $this->seedOpenBatch();
    }

    private function seedTransactionCategories(): void
    {
        $categories = [
            ['penjualan', 'Penjualan', CashFlow::Masuk, CategoryScope::Penjualan],
            ['refund', 'Refund', CashFlow::Keluar, CategoryScope::Penjualan],
            ['kredit_hp', 'Kredit HP', CashFlow::Masuk, CategoryScope::Penjualan],
            ['belanja_vendor', 'Belanja vendor', CashFlow::Keluar, CategoryScope::Batch],
            ['ongkir_internasional', 'Ongkir internasional', CashFlow::Keluar, CategoryScope::Batch],
            ['bea_masuk', 'Bea masuk', CashFlow::Keluar, CategoryScope::Batch],
            ['ongkir_domestik', 'Ongkir domestik', CashFlow::Keluar, CategoryScope::Batch],
            ['biaya_gateway', 'Biaya gateway', CashFlow::Keluar, CategoryScope::Operasional],
            ['transfer_masuk', 'Transfer masuk', CashFlow::Masuk, CategoryScope::Transfer],
            ['transfer_keluar', 'Transfer keluar', CashFlow::Keluar, CategoryScope::Transfer],
        ];

        foreach ($categories as [$slug, $name, $type, $scope]) {
            TransactionCategory::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'type' => $type, 'scope' => $scope, 'is_system' => true],
            );
        }
    }

    /**
     * Defaults from PRD §6.13. Existing values are kept so admin changes survive re-seeding.
     */
    private function seedSettings(): void
    {
        $defaults = [
            'timezone' => 'Asia/Tokyo',
            'batch_period' => 'monthly',
            'batch_cutoff_day' => 'sunday',
            'batch_cutoff_time' => '23:59',
            'eta_min_days' => '14',
            'eta_max_days' => '21',
            'payment_window_hours' => '24',
            'auto_complete_days' => '7',
            'complaint_days_after_received' => '3',
            'complaint_days_after_shipped' => '10',
            'proof_unverified_alert_hours' => '12',
            'arrived_unshipped_alert_days' => '2',
            'phone_sync_frequency' => 'monthly',
            'confirmation_reminder_business_days' => '2',
            'base_currency' => Currency::Idr->value,
            'max_design_upload_mb' => '10',
            'max_proof_upload_mb' => '5',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function seedCaseOptions(): void
    {
        $sort = 0;

        // The store only sells softcases. Starting prices; the owner adjusts them in the admin panel.
        $caseTypes = [
            'softcase' => ['Softcase', 2800, 285000, 85000],
        ];

        foreach ($caseTypes as $slug => [$name, $priceJpy, $priceIdr, $costIdr]) {
            CaseType::firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'default_price_jpy' => $priceJpy,
                'default_price_idr' => $priceIdr,
                'default_cost_price_idr' => $costIdr,
                'sort_order' => $sort++,
            ]);
        }

        $sort = 0;

        $colors = ['bening' => ['Bening', '#d9e1e8'], 'hitam' => ['Hitam', '#1d1d1f'], 'putih' => ['Putih', '#ffffff']];

        foreach ($colors as $slug => [$name, $hex]) {
            CaseColor::firstOrCreate(['slug' => $slug], ['name' => $name, 'hex_code' => $hex, 'sort_order' => $sort++]);
        }
    }

    /**
     * Account numbers are left empty on purpose: the owner fills them in the admin panel.
     */
    private function seedCashAccounts(): void
    {
        $accounts = [
            ['Bank Jepang', CashAccountType::Bank, Currency::Jpy, true],
            ['BCA', CashAccountType::Bank, Currency::Idr, true],
            ['Kas Tunai', CashAccountType::Tunai, Currency::Jpy, false],
        ];

        foreach ($accounts as [$name, $type, $currency, $showAtCheckout]) {
            CashAccount::firstOrCreate(['name' => $name], [
                'type' => $type,
                'currency' => $currency,
                'bank_name' => $type === CashAccountType::Bank ? $name : null,
                'show_at_checkout' => $showAtCheckout,
            ]);
        }
    }

    private function seedOpenBatch(): void
    {
        if (PreorderBatch::open()->exists()) {
            return;
        }

        $cutoff = BatchSchedule::nextCutoff();

        PreorderBatch::create([
            'code' => BatchSchedule::codeFor($cutoff),
            'status' => BatchStatus::Dibuka,
            'cutoff_at' => $cutoff->utc(),
        ]);
    }
}
