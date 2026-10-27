<?php

namespace App\Support\Credit;

use App\Enums\Currency;
use App\Models\Setting;
use App\Models\User;

/**
 * Credit terms the admin sets in Pengaturan → Kredit HP, and the installment maths.
 *
 * Margin is flat: a percentage of the financed amount (price − down payment) per month.
 * Credit is taken in yen or rupiah; amounts follow the currency of the credit.
 */
class CreditTerms
{
    public const DEFAULTS = [
        'credit_tenors' => '3,6,10,12',
        'credit_min_dp_percent' => '20',
        'credit_margin_percent' => '2',
        'credit_admin_fee' => '0',
        'credit_whatsapp' => '',
        'credit_verification_message' => 'Halo admin Lahiya Store, saya ingin verifikasi akun untuk kredit HP. Nama: {nama}. Email: {email}. No. HP: {telepon}.',
        'credit_terms' => "Kredit hanya untuk akun yang sudah diverifikasi admin.\nHP diserahkan setelah DP diterima.\nCicilan dibayar setiap bulan sesuai jadwal lewat transfer ke rekening toko.\nKeterlambatan pembayaran akan dihubungi admin lewat WhatsApp.",
    ];

    /**
     * @param  list<int>  $tenors
     */
    public function __construct(
        public readonly array $tenors,
        public readonly int $minDownPaymentPercent,
        public readonly float $marginPercent,
        public readonly int $adminFee,
        public readonly ?string $whatsapp,
        public readonly string $verificationMessage,
        public readonly string $terms,
    ) {}

    public static function current(): self
    {
        $value = fn (string $key): string => (string) Setting::valueOf($key, self::DEFAULTS[$key]);

        $tenors = collect(explode(',', $value('credit_tenors')))
            ->map(fn (string $tenor): int => (int) trim($tenor))
            ->filter(fn (int $tenor): bool => $tenor >= 1 && $tenor <= 36)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return new self(
            tenors: $tenors === [] ? [12] : $tenors,
            minDownPaymentPercent: max(0, min(90, (int) $value('credit_min_dp_percent'))),
            marginPercent: max(0, (float) $value('credit_margin_percent')),
            adminFee: max(0, (int) $value('credit_admin_fee')),
            whatsapp: preg_replace('/\D/', '', $value('credit_whatsapp') ?: (string) Setting::valueOf('store_whatsapp', '')) ?: null,
            verificationMessage: $value('credit_verification_message'),
            terms: $value('credit_terms'),
        );
    }

    /**
     * Down payments are rounded to ¥1.000 or Rp100.000.
     */
    public function downPaymentStep(Currency $currency = Currency::Jpy): int
    {
        return $currency === Currency::Jpy ? 1000 : 100000;
    }

    public function minimumDownPayment(int $cashPrice, Currency $currency = Currency::Jpy): int
    {
        $step = $this->downPaymentStep($currency);

        return (int) (ceil($cashPrice * $this->minDownPaymentPercent / 100 / $step) * $step);
    }

    /**
     * The admin fee is set in yen; rupiah credits use the JPY→IDR rate of the day, rounded up to Rp1.000.
     */
    public function adminFeeIn(Currency $currency, ?float $jpyToIdr): int
    {
        if ($currency === Currency::Jpy || $this->adminFee === 0) {
            return $this->adminFee;
        }

        return (int) (ceil($this->adminFee * (float) $jpyToIdr / 1000) * 1000);
    }

    /**
     * Installments are rounded up to ¥100 or Rp1.000, so every month is the same amount.
     *
     * @return array{financed: int, margin: int, admin_fee: int, monthly: int, total: int}
     */
    public function quote(int $cashPrice, int $downPayment, int $tenor, Currency $currency = Currency::Jpy, ?float $jpyToIdr = null): array
    {
        $financed = max(0, $cashPrice - $downPayment);
        $margin = (int) ceil($financed * $this->marginPercent / 100 * $tenor);
        $adminFee = $this->adminFeeIn($currency, $jpyToIdr);
        $unit = $currency === Currency::Jpy ? 100 : 1000;
        $monthly = (int) (ceil(($financed + $margin + $adminFee) / max(1, $tenor) / $unit) * $unit);

        return [
            'financed' => $financed,
            'margin' => $margin,
            'admin_fee' => $adminFee,
            'monthly' => $monthly,
            'total' => $downPayment + $monthly * $tenor,
        ];
    }

    /**
     * WhatsApp link with the verification request already typed, or null when no number is set.
     */
    public function verificationUrl(User $user): ?string
    {
        if ($this->whatsapp === null) {
            return null;
        }

        $message = strtr($this->verificationMessage, [
            '{nama}' => $user->name,
            '{email}' => (string) $user->email,
            '{telepon}' => (string) $user->phone,
        ]);

        return "https://wa.me/{$this->whatsapp}?text=".rawurlencode($message);
    }

    /**
     * Plain WhatsApp link to the credit admin, for questions about an application.
     */
    public function contactUrl(?string $message = null): ?string
    {
        if ($this->whatsapp === null) {
            return null;
        }

        return "https://wa.me/{$this->whatsapp}".($message ? '?text='.rawurlencode($message) : '');
    }
}
