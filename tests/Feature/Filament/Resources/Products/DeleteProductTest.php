<?php

namespace Tests\Feature\Filament\Resources\Products;

use App\Filament\Resources\PhoneModels\Pages\ListPhoneModels;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\OrderItem;
use App\Models\PhoneModel;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeleteProductTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_staff_deletes_an_unused_product_straight_from_the_list(): void
    {
        $this->actingAs(User::factory()->staff()->create());
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create();

        Livewire::test(ListProducts::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($product))
            ->assertNotified();

        $this->assertModelMissing($product);
        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_ordered_product_is_kept_and_the_admin_is_told_to_deactivate_it(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $variant = ProductVariant::factory()->create();
        OrderItem::factory()->create(['product_variant_id' => $variant->id]);

        Livewire::test(ListProducts::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($variant->product))
            ->assertNotified('Tidak bisa dihapus');

        $this->assertModelExists($variant->product);
        $this->assertModelExists($variant);
    }

    public function test_product_can_also_be_deleted_from_its_edit_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertRedirect(ListProducts::getUrl());

        $this->assertModelMissing($product);
    }

    public function test_admin_deletes_an_unused_phone_model(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $phoneModel = PhoneModel::factory()->create();

        Livewire::test(ListPhoneModels::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($phoneModel));

        $this->assertModelMissing($phoneModel);
    }
}
