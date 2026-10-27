<?php

use App\Http\Controllers\Admin\PrivateFileController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\SharedDesignController;
use App\Livewire\Account\Addresses;
use App\Livewire\Account\Credit;
use App\Livewire\Account\Orders;
use App\Livewire\Account\Profile;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Store\CartPage;
use App\Livewire\Store\Catalog;
use App\Livewire\Store\Checkout;
use App\Livewire\Store\CustomCase;
use App\Livewire\Store\Home;
use App\Livewire\Store\Policy;
use App\Livewire\Store\PolicyDetail;
use App\Livewire\Store\ProductShow;
use App\Livewire\Store\TrackOrder;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Home::class)->name('home');
Route::livewire('/katalog', Catalog::class)->name('catalog');
Route::livewire('/produk/{slug}', ProductShow::class)->name('product.show');
Route::livewire('/custom', CustomCase::class)->name('custom');
// The cart and checkout belong to an account, so guests are sent to the login page first.
Route::middleware('auth')->group(function () {
    Route::livewire('/keranjang', CartPage::class)->name('cart');
    Route::livewire('/checkout', Checkout::class)->name('checkout');
});
Route::livewire('/lacak', TrackOrder::class)->name('track');
Route::livewire('/kebijakan', Policy::class)->name('policy');
Route::livewire('/kebijakan/{topic}', PolicyDetail::class)
    ->whereIn('topic', array_column(PolicyDetail::TOPICS, 'slug'))
    ->name('policy.show');

Route::post('/mata-uang', CurrencyController::class)->name('currency.update');

// Expiring links written into the monthly Excel export (see ExportMonthlyPreorders).
Route::middleware('signed')->prefix('unduh')->name('shared.')->group(function () {
    Route::get('desain/{design}', [SharedDesignController::class, 'design'])->name('design');
    Route::get('batch/{batch}/desain.zip', [SharedDesignController::class, 'batch'])->name('batch-designs');
});

Route::middleware('guest')->group(function () {
    Route::livewire('/masuk', Login::class)->name('login');
    Route::livewire('/daftar', Register::class)->name('register');
    Route::livewire('/lupa-password', ForgotPassword::class)->name('password.request');
    Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', LogoutController::class)->name('logout');
    Route::livewire('/akun', Orders::class)->name('account.orders');
    Route::livewire('/akun/alamat', Addresses::class)->name('account.addresses');
    Route::livewire('/akun/profil', Profile::class)->name('account.profile');
    Route::livewire('/akun/kredit', Credit::class)->name('account.credit');
});

Route::middleware('auth')->prefix('admin/files')->name('admin.files.')->group(function () {
    Route::get('complaints/{complaint}/{kind}', [PrivateFileController::class, 'complaint'])->whereIn('kind', ['video', 'photo'])->name('complaint');
    Route::get('payments/{payment}/proof', [PrivateFileController::class, 'paymentProof'])->name('payment-proof');
    Route::get('designs/{design}', [PrivateFileController::class, 'design'])->name('design');
    Route::get('cash/{transaction}/attachment', [PrivateFileController::class, 'cashAttachment'])->name('cash-attachment');
    Route::get('orders/{order}/packing-photo', [PrivateFileController::class, 'packingPhoto'])->name('packing-photo');
    Route::get('orders/{order}/packing-slip', [PrivateFileController::class, 'packingSlip'])->name('packing-slip');
    Route::get('batches/{batch}/recap.{format}', [PrivateFileController::class, 'batchRecap'])->whereIn('format', ['zip', 'csv', 'pdf'])->name('batch-recap');
});
