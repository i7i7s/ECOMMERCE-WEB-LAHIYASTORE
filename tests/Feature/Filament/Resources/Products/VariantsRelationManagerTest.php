<?php

namespace Tests\Feature\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VariantsRelationManagerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_staff_creates_variant_without_seeing_or_setting_cost(): void
    {
        $this->actingAs(User::factory()->staff()->create());
        $product = Product::factory()->create();

        Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->assertTableColumnHidden('cost_price_idr')
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'name' => 'M - Hitam',
                'sku' => 'JKT-M-HTM',
                'price_jpy' => 4800,
                'price_idr' => 490000,
                'cost_price_idr' => 999999,
                'is_available' => true,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'JKT-M-HTM',
            'price_jpy' => 4800,
            'price_idr' => 490000,
            'cost_price_idr' => 0,
        ]);
    }

    public function test_admin_sets_variant_cost(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create();

        Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->assertTableColumnVisible('cost_price_idr')
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'name' => 'Default',
                'sku' => 'PB-1',
                'price_jpy' => 3500,
                'price_idr' => 360000,
                'cost_price_idr' => 200000,
                'is_available' => true,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('product_variants', ['sku' => 'PB-1', 'cost_price_idr' => 200000]);
    }

    public function test_rejects_duplicate_sku(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->hasVariants(1, ['sku' => 'DUP-1'])->create();

        Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'name' => 'L',
                'sku' => 'DUP-1',
                'price_jpy' => 100,
                'price_idr' => 10000,
            ])
            ->assertHasFormErrors(['sku' => 'unique']);
    }
}
