<?php

namespace App\Livewire\Store;

use App\Actions\Orders\PlaceOrder;
use App\Enums\Currency;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Support\JapanPrefectures;
use App\Support\Storefront\Cart;
use App\Support\Storefront\OrderTracker;
use App\Support\Storefront\Storefront;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Checkout')]
class Checkout extends Component
{
    #[Locked]
    public int $step = 1;

    #[Locked]
    public ?string $orderCode = null;

    public string $name = '';

    public string $phone = '';

    public string $postalCode = '';

    public string $prefecture = '';

    public string $city = '';

    public string $addressLine1 = '';

    public string $addressLine2 = '';

    public bool $agreePolicy = false;

    /** Signed-in customers: the saved address used to fill the form. */
    public ?int $addressId = null;

    /** Signed-in customers: save the typed address to their address book. */
    public bool $saveAddress = true;

    public function mount(Cart $cart): void
    {
        if ($cart->items()->isEmpty()) {
            $this->redirectRoute('cart', navigate: true);

            return;
        }

        $user = auth()->user();

        if ($user !== null) {
            $this->name = $user->name;
            $this->phone = (string) $user->phone;

            $default = $user->addresses()->orderByDesc('is_default')->oldest('id')->first();

            if ($default !== null) {
                $this->useAddress($default->id);
            }
        }
    }

    /**
     * Fills the form from one of the signed-in customer's saved addresses.
     */
    public function useAddress(int $id): void
    {
        $address = auth()->user()?->addresses()->find($id);

        if ($address === null) {
            return;
        }

        $this->addressId = $address->id;
        $this->name = $address->recipient_name;
        $this->phone = $address->phone;
        $this->postalCode = $address->postal_code;
        $this->prefecture = $address->prefecture;
        $this->city = $address->city;
        $this->addressLine1 = $address->address_line1;
        $this->addressLine2 = (string) $address->address_line2;
        $this->saveAddress = false;
    }

    public function updatedAddressId(?int $id): void
    {
        if ($id) {
            $this->useAddress($id);

            return;
        }

        // "Alamat baru" (0): start from an empty address, keep the customer's name and phone.
        $this->addressId = 0;
        $this->reset(['postalCode', 'prefecture', 'city', 'addressLine1', 'addressLine2']);
        $this->saveAddress = true;
    }

    public function toSummary(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^[0-9+\-\s]{9,16}$/'],
            'postalCode' => ['required', 'regex:/^\d{3}-?\d{4}$/'],
            'prefecture' => ['required', Rule::in(JapanPrefectures::all())],
            'city' => ['required', 'string', 'max:100'],
            'addressLine1' => ['required', 'string', 'max:150'],
            'addressLine2' => ['nullable', 'string', 'max:150'],
        ], [
            'phone.regex' => 'Nomor HP hanya boleh angka, spasi, + atau -.',
            'postalCode.regex' => 'Kode pos Jepang 7 digit, contoh 530-0001.',
        ], [
            'name' => 'nama penerima',
            'phone' => 'nomor HP',
            'postalCode' => 'kode pos',
            'prefecture' => 'prefektur',
            'city' => 'kota/ward',
            'addressLine1' => 'alamat',
        ]);

        $this->step = 2;
    }

    public function backToAddress(): void
    {
        if ($this->orderCode === null) {
            $this->step = 1;
        }
    }

    public function placeOrder(Cart $cart, PlaceOrder $placeOrder, OrderTracker $tracker): void
    {
        $this->validate(
            ['agreePolicy' => ['accepted']],
            ['agreePolicy.accepted' => 'Centang persetujuan kebijakan preorder dan refund.'],
        );

        if ($this->orderCode !== null || $cart->items()->isEmpty()) {
            return;
        }

        try {
            $order = $placeOrder->handle([
                'name' => $this->name,
                'phone' => $this->phone,
                'postal_code' => $this->postalCode,
                'prefecture' => $this->prefecture,
                'city' => $this->city,
                'address_line1' => $this->addressLine1,
                'address_line2' => $this->addressLine2 ?: null,
            ], $cart->items(), Currency::current(), OrderSource::Website, account: auth()->user());
        } catch (BusinessRuleException $exception) {
            $this->addError('order', $exception->getMessage());

            return;
        }

        $this->saveAddressToAccount();
        $cart->clear();
        $tracker->remember($order);
        $this->orderCode = $order->code;
        $this->step = 3;
        $this->dispatch('cart-updated');
    }

    private function saveAddressToAccount(): void
    {
        $user = auth()->user();

        if ($user === null || ! $this->saveAddress || $user->addresses()->count() >= 10) {
            return;
        }

        $user->addresses()->create([
            'recipient_name' => $this->name,
            'phone' => $this->phone,
            'postal_code' => $this->postalCode,
            'prefecture' => $this->prefecture,
            'city' => $this->city,
            'address_line1' => $this->addressLine1,
            'address_line2' => $this->addressLine2 ?: null,
            'is_default' => ! $user->addresses()->exists(),
        ]);
    }

    private function order(): ?Order
    {
        return $this->orderCode ? Order::query()->where('code', $this->orderCode)->first() : null;
    }

    public function render(): View
    {
        $cart = app(Cart::class);
        $storefront = app(Storefront::class);
        $currency = Currency::current();
        $priceColumn = $currency === Currency::Jpy ? 'price_jpy' : 'price_idr';
        $order = $this->order();

        return view('livewire.store.checkout', [
            'items' => $cart->items()->map(fn (array $item): array => [...$item, 'line_total' => $currency->format($item[$priceColumn] * $item['qty'])]),
            'total' => $currency->format($cart->subtotal($currency)),
            'needsConfirmation' => $cart->needsConfirmation(),
            'prefectures' => JapanPrefectures::all(),
            'savedAddresses' => auth()->user()?->addresses()->orderByDesc('is_default')->oldest('id')->get() ?? collect(),
            'batch' => $storefront->openBatch(),
            'cutoffLabel' => $storefront->cutoffLabel(),
            'etaLabel' => $storefront->etaLabel(),
            'order' => $order,
            'orderStatus' => $order?->status,
            'orderTotal' => $order?->formattedTotal(),
            'paymentDue' => $order?->status === OrderStatus::MenungguPembayaran && $order->payment_due_at
                ? $order->payment_due_at->setTimezone('Asia/Tokyo')->locale('id')->translatedFormat('l, j M Y · H.i').' JST'
                : null,
        ]);
    }
}
