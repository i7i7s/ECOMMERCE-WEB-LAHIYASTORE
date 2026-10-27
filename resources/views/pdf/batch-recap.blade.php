<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap {{ $batch->code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1d1d1f; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 22px 0 8px; }
        .muted { color: #6e6e73; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 5px 6px; border-bottom: 1px solid #d2d2d7; vertical-align: top; }
        th { background: #f5f5f7; font-size: 10px; }
        .num { text-align: right; }
        .note { margin-top: 14px; padding: 10px 12px; background: #fff4d6; border-radius: 6px; }
        .total td { font-weight: bold; border-top: 2px solid #1d1d1f; }
    </style>
</head>
<body>
    <h1>Rekap produksi {{ $batch->code }}</h1>
    <p class="muted">Lahiya Store · cutoff {{ $batch->cutoff_at->setTimezone('Asia/Tokyo')->format('d/m/Y H:i') }} JST · dicetak {{ now('Asia/Tokyo')->format('d/m/Y H:i') }} JST</p>

    <div class="note">
        <strong>Mohon:</strong> bungkus tiap casing satu per satu dan tempel label berisi <strong>nama file</strong> (diawali kode order) di bungkusnya.
        Label ini dipakai tim di Jepang untuk mencocokkan casing dengan pesanan.
    </div>

    <h2>Jumlah per tipe HP × jenis case × warna</h2>
    <table>
        <thead><tr><th>Tipe HP</th><th>Jenis case</th><th>Warna</th><th class="num">Qty</th></tr></thead>
        <tbody>
            @foreach ($summary as $row)
                <tr><td>{{ $row['phone_model'] }}</td><td>{{ $row['case_type'] }}</td><td>{{ $row['color'] }}</td><td class="num">{{ $row['qty'] }}</td></tr>
            @endforeach
            <tr class="total"><td colspan="3">Total</td><td class="num">{{ $summary->sum('qty') }}</td></tr>
        </tbody>
    </table>

    <h2>Daftar file cetak</h2>
    <table>
        <thead><tr><th>Nama file</th><th class="num">Qty</th><th>Catatan</th></tr></thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line['file_name'] }}</td>
                    <td class="num">{{ $line['qty'] }}</td>
                    <td>{{ $line['has_preview'] ? '' : 'Tanpa preview. Posisi: '.$line['position_note'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
