<?php

namespace App\Livewire\Store;

use App\Enums\Currency;
use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Support\Credit\CreditTerms;
use App\Support\Storefront\Storefront;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Katalog')]
class Catalog extends Component
{
    #[Url(as: 'kategori', except: '')]
    public string $category = '';

    #[Url(as: 'urut', except: 'terbaru')]
    public string $sort = 'terbaru';

    public function selectCategory(string $category): void
    {
        $this->category = Category::active()->where('slug', $category)->exists() ? $category : '';
    }

    /**
     * Active products that have at least one available variant, with their lowest price.
     *
     * @return Collection<int, array{slug: string, name: string, category: string, image: string|null, variants: list<string>, price: int, price_label: string}>
     */
    #[Computed]
    public function products(): Collection
    {
        $currency = Currency::current();
        $priceColumn = $currency === Currency::Jpy ? 'price_jpy' : 'price_idr';
        $terms = CreditTerms::current();

        $products = Product::active()
            ->whereHas('category', fn (Builder $query) => $query->active())
            ->whereHas('variants', fn (Builder $query) => $query->where('is_available', true))
            ->when($this->category !== '', fn (Builder $query) => $query->whereRelation('category', 'slug', $this->category))
            ->with(['category', 'images', 'variants' => fn ($query) => $query->where('is_available', true)])
            ->latest('id')
            ->get()
            ->map(fn (Product $product): array => $product->isSoldOnCredit() ? [...$this->creditCard($product, $terms, $currency), 'sort_price' => (int) $product->variants->min($priceColumn)] : [
                'slug' => $product->slug,
                'name' => $product->name,
                'category' => $product->category->name,
                'image' => $product->images->first()?->path,
                'variants' => $product->variants->pluck('name')->all(),
                'price' => (int) $product->variants->min($priceColumn),
                'has_price_range' => $product->variants->pluck($priceColumn)->unique()->count() > 1,
                'credit' => false,
                'monthly_label' => null,
                'sort_price' => (int) $product->variants->min($priceColumn),
            ]);

        $products = match ($this->sort) {
            'termurah' => $products->sortBy('sort_price'),
            'termahal' => $products->sortByDesc('sort_price'),
            default => $products,
        };

        return $products
            ->map(fn (array $product): array => [...$product, 'price_label' => ($product['has_price_range'] ? 'Mulai ' : '').$currency->format($product['price'])])
            ->values();
    }

    /**
     * Card of a phone sold on credit: price, storages and colours, and the lowest monthly installment,
     * in the visitor's currency.
     *
     * @return array<string, mixed>
     */
    private function creditCard(Product $product, CreditTerms $terms, Currency $currency): array
    {
        $column = $currency === Currency::Jpy ? 'price_jpy' : 'price_idr';
        $lowest = (int) $product->variants->min($column);
        $rate = ExchangeRate::effectiveOn();
        $quote = $terms->quote($lowest, $terms->minimumDownPayment($lowest, $currency), max($terms->tenors), $currency, $rate ? (float) $rate->jpy_to_idr : null);
        $colors = $product->variants->pluck('color_name')->filter()->unique()->count();

        return [
            'slug' => $product->slug,
            'name' => $product->name,
            'category' => $product->category->name,
            'image' => $product->images->first()?->path,
            'variants' => [
                ...$product->variants->sortBy('storage_gb')->map(fn ($variant): ?string => $variant->storageLabel())->filter()->unique()->values()->all(),
                ...($colors > 0 ? ["{$colors} warna"] : []),
            ],
            'price' => $lowest,
            'has_price_range' => $product->variants->pluck($column)->unique()->count() > 1,
            'credit' => true,
            'monthly_label' => 'Cicilan mulai '.$currency->format($quote['monthly']).'/bln',
        ];
    }

    public function render(): View
    {
        $storefront = app(Storefront::class);
        $currency = Currency::current();
        $customPrice = $storefront->lowestCustomCasePrice($currency);

        return view('livewire.store.catalog', [
            'categories' => Category::active()->orderBy('sort_order')->pluck('name', 'slug')->all(),
            'etaLabel' => $storefront->etaLabel(),
            'cutoffLabel' => $storefront->cutoffLabel(),
            'customPrice' => $customPrice === null ? null : $currency->format($customPrice),
        ]);
    }
}
