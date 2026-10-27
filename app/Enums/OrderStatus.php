<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Order lifecycle from PRD §6.3; allowed transitions follow the state diagram in §16.2.
 */
enum OrderStatus: string implements HasColor, HasLabel
{
    case MenungguKonfirmasi = 'menunggu_konfirmasi';
    case MenungguPembayaran = 'menunggu_pembayaran';
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Dibayar = 'dibayar';
    case Diproduksi = 'diproduksi';
    case DikirimKeJepang = 'dikirim_ke_jepang';
    case TibaDiJepang = 'tiba_di_jepang';
    case Dikirim = 'dikirim';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::MenungguKonfirmasi => 'Menunggu konfirmasi',
            self::MenungguPembayaran => 'Menunggu pembayaran',
            self::MenungguVerifikasi => 'Menunggu verifikasi',
            self::Dibayar => 'Dibayar',
            self::Diproduksi => 'Diproduksi',
            self::DikirimKeJepang => 'Dikirim ke Jepang',
            self::TibaDiJepang => 'Tiba di Jepang',
            self::Dikirim => 'Dikirim',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MenungguKonfirmasi, self::MenungguPembayaran, self::MenungguVerifikasi => 'warning',
            self::Dibayar, self::Diproduksi => 'primary',
            self::DikirimKeJepang, self::TibaDiJepang, self::Dikirim => 'info',
            self::Selesai => 'success',
            self::Dibatalkan => 'gray',
        };
    }

    /**
     * Statuses this status may move to.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::MenungguKonfirmasi => [self::MenungguPembayaran, self::Dibatalkan],
            self::MenungguPembayaran => [self::MenungguVerifikasi, self::Dibayar, self::Dibatalkan],
            self::MenungguVerifikasi => [self::MenungguPembayaran, self::Dibayar, self::Dibatalkan],
            self::Dibayar => [self::Diproduksi, self::Dibatalkan],
            self::Diproduksi => [self::DikirimKeJepang],
            self::DikirimKeJepang => [self::TibaDiJepang],
            self::TibaDiJepang => [self::Dikirim],
            self::Dikirim => [self::Selesai],
            self::Selesai, self::Dibatalkan => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Customer-facing explanation shown on the tracking page.
     */
    public function description(): string
    {
        return match ($this) {
            self::MenungguKonfirmasi => 'Tim sedang mengecek ketersediaan blank case ke vendor. Kamu akan dikabari sebelum membayar.',
            self::MenungguPembayaran => 'Selesaikan pembayaran dan upload bukti transfer sebelum batas waktu.',
            self::MenungguVerifikasi => 'Bukti transfer sudah kami terima dan sedang dicek admin.',
            self::Dibayar => 'Pembayaran diterima. Pesananmu masuk batch preorder bulan ini.',
            self::Diproduksi => 'Casingmu sedang dicetak vendor di Indonesia. Pesanan sudah tidak bisa dibatalkan.',
            self::DikirimKeJepang => 'Paket batch sedang dalam perjalanan ke Jepang.',
            self::TibaDiJepang => 'Paket sudah tiba di Jepang dan sedang dikemas.',
            self::Dikirim => 'Pesanan sudah diserahkan ke kurir.',
            self::Selesai => 'Pesanan sudah diterima. Terima kasih!',
            self::Dibatalkan => 'Pesanan dibatalkan.',
        };
    }

    /**
     * Tailwind classes for the status badge (label + tinted background).
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::MenungguKonfirmasi => 'bg-confirm-soft text-confirm',
            self::MenungguPembayaran => 'bg-warn-soft text-warn',
            self::MenungguVerifikasi => 'bg-[#fdeee2] text-[#a14a00]',
            self::Dibayar, self::Dikirim => 'bg-info-soft text-info',
            self::Diproduksi => 'bg-[#ecebfb] text-[#4338a8]',
            self::DikirimKeJepang => 'bg-[#e3f4f4] text-[#0b6b6b]',
            self::TibaDiJepang => 'bg-[#e2f1fb] text-[#065b8c]',
            self::Selesai => 'bg-good-soft text-good',
            self::Dibatalkan => 'bg-fog text-ink-2',
        };
    }

    /**
     * The happy path shown as a timeline to customers, in order.
     *
     * @return list<self>
     */
    public static function timeline(): array
    {
        return [
            self::MenungguPembayaran,
            self::Dibayar,
            self::Diproduksi,
            self::DikirimKeJepang,
            self::TibaDiJepang,
            self::Dikirim,
            self::Selesai,
        ];
    }
}
