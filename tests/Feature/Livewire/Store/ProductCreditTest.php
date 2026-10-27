<?php

namespace Tests\Feature\Livewire\Store;

use App\Enums\CreditApplicationStatus;
use App\Enums\CreditVerification;
use App\Enums\Currency;
use App\Livewire\Account\Credit;
use App\Livewire\Store\ProductShow;
use App\Models\Category;
use App\Models\CreditApplication;
use App\Models\CreditInstallment;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCreditTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['key' => 'store_whatsapp', 'value' => '+81 90-1234-5678']);
        $this->product = Product::factory()->credit()->for(Category::factory()->create(['slug' => 'iphone']))->create(['name' => 'iPhone 18 Pro', 'slug' => 'iphone-18-pro']);
        ProductVariant::factory()->phone('Black', 256, 219800)->for($this->product)->create();
        ProductVariant::factory()->phone('Black', 512, 254800)->for($this->product)->create();
        ProductVariant::factory()->phone('Burgundy', 256, 219800)->for($this->product)->create();
        ProductImage::factory()->for($this->product)->create(['path' => 'products/iphone/black.svg', 'color_name' => 'Black']);
        ProductImage::factory()->for($this->product)->create(['path' => 'products/iphone/burgundy.svg', 'color_name' => 'Burgundy']);
    }

    public function test_guest_sees_the_simulator_and_is_asked_to_sign_in(): void
    {
        $this->get(route('product.show', 'iphone-18-pro'))
            ->assertOk()
            ->assertSee('Simulasi kredit')
            ->assertSee('Masuk untuk ajukan kredit')
            ->assertSee('kembali=produk%2Fiphone-18-pro', false)
            ->assertDontSee('Tambah ke keranjang');
    }

    public function test_choosing_a_colour_switches_pictures_and_storages(): void
    {
        Livewire::test(ProductShow::class, ['slug' => 'iphone-18-pro'])
            ->assertSet('color', 'Black')
            ->assertSet('storage', 256)
            ->assertSee('black.svg')
            ->assertSee('512 GB')
            ->set('color', 'Burgundy')
            ->assertSet('storage', 256)
            ->assertSee('burgundy.svg')
            ->assertDontSee('black.svg')
            ->assertDontSee('512 GB');
    }

    public function test_simulator_shows_the_monthly_installment_and_keeps_the_down_payment_valid(): void
    {
        // ¥219.800 → minimum DP 20% rounded up to ¥44.000; ¥175.800 + 2% × 12 = ¥217.992 → ¥18.200/month.
        Livewire::test(ProductShow::class, ['slug' => 'iphone-18-pro'])
            ->assertSet('downPayment', 44000)
            ->assertSet('tenor', 12)
            ->assertSee('¥18.200')
            ->set('downPayment', 1000)
            ->assertSee('DP minimal ¥44.000 dan harus lebih kecil dari harga HP.')
            ->set('storage', 512)
            ->assertSet('downPayment', 51000);
    }

    public function test_unverified_customer_gets_a_prefilled_whatsapp_link_and_is_marked_as_asking(): void
    {
        $customer = User::factory()->create(['name' => 'Sari', 'phone' => '08055556666']);
        $this->actingAs($customer);

        Livewire::test(ProductShow::class, ['slug' => 'iphone-18-pro'])
            ->assertSee('Verifikasi via WhatsApp')
            ->assertSee('https://wa.me/819012345678?text=', false)
            ->assertSee(rawurlencode('Nama: Sari'), false)
            ->call('requestVerification')
            ->assertSee('Menunggu verifikasi admin');

        $this->assertSame(CreditVerification::Diajukan, $customer->refresh()->credit_status);
    }

    public function test_verified_customer_applies_and_lands_on_the_credit_page(): void
    {
        $customer = User::factory()->creditVerified()->create();
        $this->actingAs($customer);

        Livewire::test(ProductShow::class, ['slug' => 'iphone-18-pro'])
            ->set('downPayment', 60000)
            ->set('tenor', 10)
            ->set('creditNote', 'Ambil di Osaka')
            ->call('applyCredit')
            ->assertHasNoErrors()
            ->assertRedirect(route('account.credit'));

        $application = CreditApplication::sole();
        $this->assertTrue($application->user->is($customer));
        $this->assertSame([60000, 10, 'Ambil di Osaka'], [$application->down_payment, $application->tenor_months, $application->customer_note]);
    }

    public function test_business_rule_errors_are_shown_on_the_page(): void
    {
        $this->actingAs(User::factory()->creditVerified(10000)->create());

        Livewire::test(ProductShow::class, ['slug' => 'iphone-18-pro'])
            ->call('applyCredit')
            ->assertHasErrors('credit')
            ->assertSee('melebihi sisa batas kredit');

        $this->assertSame(0, CreditApplication::count());
    }

    public function test_credit_product_cannot_be_added_to_the_cart(): void
    {
        Livewire::test(ProductShow::class, ['slug' => 'iphone-18-pro'])
            ->call('addToCart')
            ->assertHasErrors('variantId');
    }

    public function test_catalog_marks_credit_phones_with_their_monthly_installment(): void
    {
        $this->get(route('catalog', ['kategori' => 'iphone']))
            ->assertOk()
            ->assertSee('Bisa kredit')
            ->assertSee('Mulai ¥219.800')
            ->assertSee('Cicilan mulai ¥18.200/bln')
            ->assertSee('256 GB · 512 GB · 2 warna');
    }

    public function test_switching_to_rupiah_shows_rupiah_prices_and_installments(): void
    {
        ExchangeRate::factory()->create(['jpy_to_idr' => 110]);

        // ¥219.800 × 110 = Rp24.178.000; DP 20% → Rp4.900.000; Rp19.278.000 + 2% × 12 → Rp1.993.000/month.
        $this->withSession(['currency' => 'IDR'])
            ->get(route('catalog', ['kategori' => 'iphone']))
            ->assertSee('Mulai Rp24.178.000')
            ->assertSee('Cicilan mulai Rp1.993.000/bln');

        $this->withSession(['currency' => 'IDR'])
            ->get(route('product.show', 'iphone-18-pro'))
            ->assertSee('Harga Rp24.178.000')
            ->assertSee('Kredit dalam Rupiah');
    }

    public function test_verified_customer_applies_in_rupiah_when_rupiah_is_selected(): void
    {
        ExchangeRate::factory()->create(['jpy_to_idr' => 110]);
        $this->actingAs(User::factory()->creditVerified()->create());
        session(['currency' => 'IDR']);

        Livewire::test(ProductShow::class, ['slug' => 'iphone-18-pro'])
            ->assertSet('downPayment', 4900000)
            ->call('applyCredit')
            ->assertHasNoErrors();

        $application = CreditApplication::sole();
        $this->assertSame(Currency::Idr, $application->currency);
        $this->assertSame(24178000, $application->cash_price);
    }

    public function test_account_page_shows_the_schedule_and_lets_the_customer_cancel_a_new_application(): void
    {
        $customer = User::factory()->creditVerified()->create();
        $running = CreditApplication::factory()->for($customer)->status(CreditApplicationStatus::Berjalan)->create(['item_name' => 'iPhone 17 · 256 GB · Sage']);
        CreditInstallment::factory()->for($running, 'application')->create(['sequence' => 1, 'due_date' => now('Asia/Tokyo')->subDays(3)->toDateString()]);
        $new = CreditApplication::factory()->for($customer)->create();

        $this->actingAs($customer);

        Livewire::test(Credit::class)
            ->assertSee('iPhone 17 · 256 GB · Sage')
            ->assertSee('Terlambat')
            ->assertSee('Terverifikasi')
            ->call('cancel', $new->id)
            ->assertHasNoErrors();

        $this->assertSame(CreditApplicationStatus::Dibatalkan, $new->refresh()->status);
    }

    public function test_customers_cannot_cancel_someone_elses_credit(): void
    {
        $other = CreditApplication::factory()->create();
        $this->actingAs(User::factory()->create());

        Livewire::test(Credit::class)
            ->call('cancel', $other->id)
            ->assertNotFound();

        $this->assertSame(CreditApplicationStatus::Diajukan, $other->refresh()->status);
    }
}
