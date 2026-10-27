<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Packing slip {{ $order->code }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', 'Segoe UI', sans-serif; color: #1d1d1f; }
        .slip { max-width: 560px; margin: 0 auto; border: 1px solid #d2d2d7; border-radius: 16px; padding: 24px; }
        .code { font-size: 26px; font-weight: 700; letter-spacing: -0.02em; margin: 0; }
        .muted { color: #6e6e73; font-size: 13px; }
        h2 { font-size: 13px; text-transform: uppercase; letter-spacing: 0.04em; color: #6e6e73; margin: 22px 0 8px; }
        .address { font-size: 16px; line-height: 1.5; }
        .item { display: flex; gap: 14px; align-items: center; padding: 10px 0; border-top: 1px solid #e8e8ed; }
        .thumb { width: 52px; height: 92px; border-radius: 10px; object-fit: cover; background: #f5f5f7; flex-shrink: 0; }
        .item-name { font-size: 15px; font-weight: 600; }
        .qty { margin-left: auto; font-size: 18px; font-weight: 700; }
        .thanks { margin-top: 22px; font-size: 13px; color: #424245; }
        .print { display: block; margin: 16px auto 0; padding: 10px 22px; border: 0; border-radius: 999px; background: #0071e3; color: #fff; font-size: 15px; cursor: pointer; }
        @media print { .print { display: none; } body { padding: 0; } .slip { border: 0; } }
    </style>
</head>
<body>
    <div class="slip">
        <p class="muted">Lahiya Store · Packing slip</p>
        <p class="code">{{ $order->code }}</p>
        <p class="muted">Batch {{ $order->batch?->code ?? '-' }}</p>

        <h2>Penerima</h2>
        <div class="address">
            <strong>{{ $order->customer_name }}</strong> · {{ $order->customer_phone }}<br>
            〒{{ $order->postal_code }} {{ $order->prefecture }}, {{ $order->city }}<br>
            {{ $order->address_line1 }}{{ $order->address_line2 ? ', '.$order->address_line2 : '' }}
        </div>

        <h2>Isi paket</h2>
        @foreach ($order->items as $item)
            <div class="item">
                @if ($item->design?->original_path)
                    <img class="thumb" src="{{ route('admin.files.design', $item->design) }}" alt="">
                @endif
                <div>
                    <div class="item-name">{{ $item->item_name }}</div>
                    @if ($item->design)
                        <div class="muted">{{ $item->design->phone_model_text }} · {{ $item->design->caseType->name }} · {{ $item->design->caseColor->name }}</div>
                    @endif
                </div>
                <div class="qty">×{{ $item->qty }}</div>
            </div>
        @endforeach

        <p class="thanks">Terima kasih sudah belanja di Lahiya Store. Ada masalah dengan pesananmu? Hubungi kami maksimal 3 hari setelah paket diterima, sertakan video unboxing.</p>
    </div>
    <button class="print" onclick="window.print()">Cetak</button>
</body>
</html>
