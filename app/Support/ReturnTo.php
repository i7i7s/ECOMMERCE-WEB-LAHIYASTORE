<?php

namespace App\Support;

/**
 * Where to send a customer after signing in, from a "?kembali=" query value.
 * Only known pages are accepted, so the parameter cannot redirect to other sites.
 */
class ReturnTo
{
    /**
     * @var array<string, string> query value => route name
     */
    private const ALLOWED = [
        'checkout' => 'checkout',
        'keranjang' => 'cart',
        'custom' => 'custom',
        'kredit' => 'account.credit',
    ];

    public static function rememberFromQuery(): void
    {
        $value = (string) request()->query('kembali');

        // A product page, e.g. "produk/iphone-17-pro" from the credit flow.
        if (preg_match('/^produk\/([a-z0-9-]{1,120})$/', $value, $matches) === 1) {
            session()->put('url.intended', route('product.show', $matches[1]));

            return;
        }

        $route = self::ALLOWED[$value] ?? null;

        if ($route !== null) {
            session()->put('url.intended', route($route));
        }
    }
}
