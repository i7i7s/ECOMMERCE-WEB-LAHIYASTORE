<?php

namespace App\Livewire\Store;

use App\Actions\Credit\RequestCreditVerification;
use App\Actions\Credit\SubmitCreditApplication;
use App\Enums\CreditVerification;
use App\Enums\Currency;
use App\Exceptions\BusinessRuleException;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Credit\CreditTerms;
use App\Support\Storefront\Cart;
use App\Support\Storefront\Storefront;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Product page. Preorder products go to the cart; phones sold on credit get a colour and
 * storage picker, an installment simulator and the credit application (verified accounts only).
 */
class ProductShow extends Component
{
    #[Locked]
    public int $productId;

    public ?int $variantId = null;

    public int $qty = 1;

    public bool $added = false;

    /** Credit products: chosen colour and storage, and the simulator inputs. */
    public string $color = '';

    public ?int $storage = null;

    public int $tenor = 0;

    public int $downPayment = 0;

    public string $creditNote = '';

    public function mount(string $slug): void
    {
        $product = Product::active()
            ->where('slug', $slug)
            ->whereRelation('category', 'is_active', true)
            ->firstOrFail();

        $this->productId = $product->id;
        $first = $product->variants()->where('is_available', true)->orderBy('storage_gb')->orderBy('id')->first();

        abort_if($first === null, 404);

        $this->variantId = $first->id;

        if ($product->isSoldOnCredit()) {
            $terms = CreditTerms::current();
            $this->color = (string) $first->color_name;
            $this->storage = $first->storage_gb;
            $this->tenor = in_array(12, $terms->tenors, true) ? 12 : (int) end($terms->tenors);
            $this->downPayment = $terms->minimumDownPayment($first->priceIn(Currency::current()), Currency::current());
        }
    }

    public function addToCart(Cart $cart): void
    {
        $this->validate([
            'variantId' => ['required', Rule::exists(ProductVariant::class, 'id')->where('product_id', $this->productId)->where('is_available', true)],
            'qty' => ['required', 'integer', 'min:1', 'max:'.Cart::MAX_QTY],
        ], ['variantId.exists' => 'Varian ini sedang tidak tersedia.']);

        $variant = ProductVariant::with('product.category', 'product.images')->findOrFail($this->variantId);

        if ($variant->product->isSoldOnCredit()) {
            $this->addError('variantId', 'Produk ini hanya dijual lewat kredit.');

            return;
        }

        $cart->add([
            'type' => 'product',
            'refs' => ['product_variant_id' => $variant->id],
            'name' => $variant->product->name,
            'detail' => $variant->name === 'Default' ? $variant->product->category->name : 'Varian '.$variant->name,
            'price_jpy' => $variant->price_jpy,
            'price_idr' => $variant->price_idr,
            'qty' => $this->qty,
            'image' => $variant->product->images->first()?->path,
        ]);

        $this->added = true;
        $this->dispatch('cart-updated');
    }

    public function updatedColor(): void
    {
        $this->syncCreditVariant();
    }

    public function updatedStorage(): void
    {
        $this->syncCreditVariant();
    }

    public function updatedDownPayment(): void
    {
        $this->downPayment = max(0, $this->downPayment);
    }

    /**
     * Marks the account as waiting for verification; the link itself opens WhatsApp.
     */
    public function requestVerification(RequestCreditVerification $requestVerification): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        try {
            $requestVerification->handle($user);
        } catch (BusinessRuleException $exception) {
            $this->addError('credit', $exception->getMessage());
        }
    }

    public function applyCredit(SubmitCreditApplication $submitCreditApplication): void
    {
        $user = auth()->user();

        if ($user === null) {
            $this->redirectRoute('login', ['kembali' => 'produk/'.$this->product()->slug], navigate: true);

            return;
        }

        $this->validate([
            'tenor' => ['required', 'integer'],
            'downPayment' => ['required', 'integer', 'min:0'],
            'creditNote' => ['nullable', 'string', 'max:500'],
        ]);

        $variant = $this->creditVariant();

        if ($variant === null) {
            $this->addError('credit', 'Kombinasi warna dan kapasitas ini sedang tidak tersedia.');

            return;
        }

        try {
            $application = $submitCreditApplication->handle($user, $variant, $this->downPayment, $this->tenor, $this->creditNote, Currency::current());
        } catch (BusinessRuleException $exception) {
            $this->addError('credit', $exception->getMessage());

            return;
        }

        session()->flash('credit_submitted', $application->code);
        $this->redirectRoute('account.credit', navigate: true);
    }

    private function product(): Product
    {
        return Product::with(['category', 'images', 'variants' => fn ($query) => $query->where('is_available', true)->orderBy('storage_gb')->orderBy('id')])
            ->findOrFail($this->productId);
    }

    private function creditVariant(?Product $product = null): ?ProductVariant
    {
        $variants = ($product ?? $this->product())->variants;

        return $variants->first(fn (ProductVariant $variant): bool => $variant->color_name === $this->color && $variant->storage_gb === $this->storage);
    }

    /**
     * Keeps colour + storage pointing at an available variant and the down payment valid.
     */
    private function syncCreditVariant(): void
    {
        $product = $this->product();
        $variant = $this->creditVariant($product)
            ?? $product->variants->firstWhere('color_name', $this->color)
            ?? $product->variants->first();

        $this->color = (string) $variant->color_name;
        $this->storage = $variant->storage_gb;
        $this->variantId = $variant->id;

        $terms = CreditTerms::current();
        $currency = Currency::current();
        $price = $variant->priceIn($currency);

        if ($this->downPayment < $terms->minimumDownPayment($price, $currency) || $this->downPayment >= $price) {
            $this->downPayment = $terms->minimumDownPayment($price, $currency);
        }
    }

    public function render(): View
    {
        $product = $this->product();
        $storefront = app(Storefront::class);

        if (! $product->isSoldOnCredit()) {
            $currency = Currency::current();
            $selected = $product->variants->firstWhere('id', $this->variantId) ?? $product->variants->first();

            return view('livewire.store.product-show', [
                'product' => $product,
                'price' => $currency->format($selected->priceIn($currency)),
                'etaLabel' => $storefront->etaLabel(),
                'batch' => $storefront->openBatch(),
                'maxQty' => Cart::MAX_QTY,
            ])->title($product->name);
        }

        return view('livewire.store.product-credit', [
            'product' => $product,
            ...$this->creditViewData($product),
        ])->title($product->name);
    }

    /**
     * @return array<string, mixed>
     */
    private function creditViewData(Product $product): array
    {
        $terms = CreditTerms::current();
        $currency = Currency::current();
        $rate = ExchangeRate::effectiveOn();
        $variant = $this->creditVariant($product) ?? $product->variants->first();
        $price = $variant->priceIn($currency);
        $minimum = $terms->minimumDownPayment($price, $currency);
        $quote = $terms->quote($price, max($this->downPayment, 0), max($this->tenor, 1), $currency, $rate ? (float) $rate->jpy_to_idr : null);
        $user = auth()->user();

        /** @var Collection<string, string> $colors */
        $colors = $product->variants->whereNotNull('color_name')->unique('color_name')->mapWithKeys(fn (ProductVariant $option): array => [$option->color_name => (string) $option->color_hex]);
        $images = $product->images->where('color_name', $this->color)->values();

        return [
            'terms' => $terms,
            'variant' => $variant,
            'money' => $currency,
            'price' => $price,
            'step' => $terms->downPaymentStep($currency),
            'colors' => $colors,
            'storages' => $product->variants->where('color_name', $this->color)->values(),
            'images' => $images->isEmpty() ? $product->images : $images,
            'minimumDownPayment' => $minimum,
            'downPaymentValid' => $this->downPayment >= $minimum && $this->downPayment < $price,
            'quote' => $quote,
            'user' => $user,
            'creditState' => match (true) {
                $user === null => 'guest',
                $user->canBuyOnCredit() => 'verified',
                default => $user->credit_status->value,
            },
            'verificationUrl' => $user ? $terms->verificationUrl($user) : null,
            'remainingLimit' => $user?->credit_limit !== null ? max(0, $user->credit_limit - $user->openCreditFinanced()) : null,
            'loginUrl' => route('login', ['kembali' => 'produk/'.$product->slug]),
            'rejected' => $user?->credit_status === CreditVerification::Ditolak,
        ];
    }
}
