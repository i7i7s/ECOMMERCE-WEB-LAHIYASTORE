<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    /**
     * Store the visitor's chosen currency in the session.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', Rule::enum(Currency::class)],
        ]);

        $request->session()->put('currency', $validated['currency']);

        return back();
    }
}
