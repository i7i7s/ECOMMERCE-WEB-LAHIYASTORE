<?php

namespace App\Support\Storefront;

use App\Models\Setting;
use App\Support\BatchSchedule;

class PolicyContent
{
    /** @return array<string, array{string, string}> */
    public static function sections(): array
    {
        $cutoff = app(Storefront::class)->cutoffLabel();
        $min = Setting::valueOf('eta_min_days', '14');
        $max = Setting::valueOf('eta_max_days', '21');
        $paymentHours = Setting::valueOf('payment_window_hours', '24');
        $receivedDays = Setting::valueOf('complaint_days_after_received', '3');
        $shippedDays = Setting::valueOf('complaint_days_after_shipped', '10');
        $sections = [
            'preorder' => ['Preorder '.BatchSchedule::periodLabel(), "Semua produk dijual dengan sistem preorder. Pesanan yang sudah dibayar masuk batch aktif.\nCutoff batch saat ini: {$cutoff}.\nCasing dicetak di Indonesia, lalu dikirim bersama ke Jepang. Estimasi tiba {$min}–{$max} hari setelah batch ditutup.\nPengiriman di Jepang gratis ongkir. Kami hanya mengirim ke alamat di Jepang."],
            'payment' => ['Pembayaran', "Bayar dalam Yen (transfer bank Jepang) atau Rupiah (transfer BCA), lalu upload bukti transfer.\nBatas waktu bayar {$paymentHours} jam. Pesanan yang belum dibayar akan batal otomatis.\nPesanan dengan tipe HP tanpa preview dikonfirmasi tim dulu sebelum kamu membayar."],
            'cancellation' => ['Pembatalan', "Sudah dibayar tapi batch belum diproduksi: boleh batal, refund penuh.\nSudah berstatus Diproduksi atau setelahnya: tidak bisa dibatalkan, karena casing dicetak khusus untukmu."],
            'complaints' => ['Komplain, retur & refund', "Ajukan komplain maksimal {$receivedDays} hari setelah paket diterima (paling lambat {$shippedDays} hari setelah dikirim), disertai video unboxing dan foto.\nKesalahan toko atau vendor (salah kirim, salah tipe HP, salah cetak, cacat, rusak di jalan): pilih cetak ulang gratis di batch berikutnya atau refund penuh.\nTidak ditanggung: perbedaan warna wajar antara layar dan hasil cetak, gambar buram atau beresolusi rendah dari pelanggan, kerusakan karena pemakaian, atau salah memilih tipe HP.\nRefund dikembalikan dalam mata uang dan ke jenis rekening yang sama dengan pembayaran, maksimal 7 hari kerja setelah disetujui."],
        ];
        foreach ($sections as $key => [$title, $default]) {
            $sections[$key] = [$title, Setting::valueOf('policy_'.$key) ?: $default];
        }

        return $sections;
    }
}
