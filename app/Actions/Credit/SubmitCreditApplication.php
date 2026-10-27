<?php

namespace App\Actions\Credit;

use App\Enums\Currency;
use App\Exceptions\BusinessRuleException;
use App\Models\CreditApplication;
use App\Models\ExchangeRate;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Credit\CreditTerms;
use App\Support\CreditCode;
use App\Support\OperationalAlerts;
use Illuminate\Support\Facades\DB;

/**
 * A verified customer applies to buy a phone on credit. Price and terms are read again from
 * the database and settings here (never trusted from the browser) and kept as a snapshot.
 */
class SubmitCreditApplication
{
    public function handle(User $customer, ProductVariant $variant, int $downPayment, int $tenor, ?string $note = null, Currency $currency = Currency::Jpy): CreditApplication
    {
        if (! $customer->canBuyOnCredit()) {
            throw new BusinessRuleException('Akunmu belum terverifikasi untuk kredit. Hubungi admin lewat WhatsApp dulu.');
        }

        if (blank($customer->phone)) {
            throw new BusinessRuleException('Isi nomor HP di profil dulu supaya admin bisa menghubungimu.');
        }

        $variant->loadMissing('product');

        if (! $variant->is_available || ! $variant->product->is_active || ! $variant->product->isSoldOnCredit()) {
            throw new BusinessRuleException('Varian ini sedang tidak tersedia untuk kredit.');
        }

        $terms = CreditTerms::current();
        $price = $variant->priceIn($currency);
        $rate = ExchangeRate::effectiveOn() ?? ($currency === Currency::Idr ? throw new BusinessRuleException('Kurs JPY→IDR belum diatur admin. Coba lagi nanti.') : null);
        $money = fn (int $amount): string => $currency->format($amount);

        if (! in_array($tenor, $terms->tenors, true)) {
            throw new BusinessRuleException('Pilih tenor yang tersedia.');
        }

        if ($downPayment < $terms->minimumDownPayment($price, $currency)) {
            throw new BusinessRuleException('DP minimal '.$money($terms->minimumDownPayment($price, $currency)).'.');
        }

        if ($downPayment >= $price) {
            throw new BusinessRuleException('DP harus lebih kecil dari harga HP. Untuk bayar lunas, hubungi admin.');
        }

        $quote = $terms->quote($price, $downPayment, $tenor, $currency, $rate ? (float) $rate->jpy_to_idr : null);

        if ($customer->credit_limit !== null) {
            $remaining = $customer->credit_limit - $customer->openCreditFinanced();
            $financedYen = $currency === Currency::Jpy ? $quote['financed'] : (int) ceil($quote['financed'] / (float) $rate->jpy_to_idr);

            if ($financedYen > $remaining) {
                throw new BusinessRuleException('Jumlah yang dicicil melebihi sisa batas kredit akunmu (¥'.number_format(max(0, $remaining), 0, ',', '.').'). Naikkan DP atau pilih varian lain.');
            }
        }

        $application = DB::transaction(fn (): CreditApplication => CreditApplication::create([
            'code' => CreditCode::next(),
            'user_id' => $customer->id,
            'product_variant_id' => $variant->id,
            'item_name' => collect([$variant->product->name, $variant->storageLabel(), $variant->color_name])->filter()->implode(' · ') ?: $variant->name,
            'currency' => $currency,
            'exchange_rate' => $rate?->jpy_to_idr,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'cash_price' => $price,
            'down_payment' => $downPayment,
            'tenor_months' => $tenor,
            'margin_percent' => $terms->marginPercent,
            'admin_fee' => $quote['admin_fee'],
            'monthly_installment' => $quote['monthly'],
            'total_payable' => $quote['total'],
            'customer_note' => filled($note) ? mb_substr(trim($note), 0, 500) : null,
        ]));

        OperationalAlerts::send(
            'Pengajuan kredit baru',
            "{$application->code}: {$customer->name} mengajukan kredit {$application->item_name}.",
            route('filament.admin.resources.credit-applications.view', ['record' => $application->id]),
        );

        return $application;
    }
}
