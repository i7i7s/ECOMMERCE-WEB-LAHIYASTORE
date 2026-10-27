<?php

namespace Tests\Feature\Livewire\Store;

use App\Livewire\Store\CustomCase;
use App\Models\CaseColor;
use App\Models\CaseType;
use App\Models\CustomCaseOption;
use App\Models\PhoneModel;
use App\Support\Storefront\Cart;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CustomCaseTest extends TestCase
{
    use LazilyRefreshDatabase;

    private PhoneModel $iphone;

    private CaseType $softcase;

    private CaseType $hardcase;

    private CaseColor $black;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->softcase = CaseType::factory()->create(['name' => 'Softcase', 'slug' => 'softcase', 'sort_order' => 0, 'default_price_jpy' => 2800]);
        $this->hardcase = CaseType::factory()->create(['name' => 'Hardcase', 'slug' => 'hardcase', 'sort_order' => 1, 'default_price_jpy' => 3000]);
        CaseColor::factory()->create(['name' => 'Bening', 'slug' => 'bening', 'sort_order' => 0]);
        $this->black = CaseColor::factory()->create(['name' => 'Hitam', 'slug' => 'hitam', 'sort_order' => 1]);

        $this->iphone = PhoneModel::factory()->withPreview(78, 163)->create(['name' => 'iPhone 17 Pro Max', 'is_featured' => true]);
        CustomCaseOption::factory()->for($this->iphone)->for($this->softcase)->create(['price_jpy' => 2800]);
        CustomCaseOption::factory()->for($this->iphone)->for($this->hardcase)->create(['price_jpy' => 3200]);
    }

    /**
     * The editor after the customer picked an image (uploaded to the server right away).
     */
    private function editor(): Testable
    {
        return Livewire::test(CustomCase::class)->set('designImage', UploadedFile::fake()->image('desain.png', 1080, 1920));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function design(array $overrides = []): array
    {
        return [
            'file_name' => 'desain.png',
            'image_width' => 1080,
            'image_height' => 1920,
            'zoom' => 1,
            'rotation' => 0,
            'thumbnail' => 'data:image/jpeg;base64,AAAA',
            'position_note' => null,
            'agree_rights' => true,
            'agree_color' => true,
            'agree_no_preview' => null,
            ...$overrides,
        ];
    }

    public function test_opens_with_the_featured_model_that_has_a_preview(): void
    {
        Livewire::test(CustomCase::class)
            ->assertSet('phoneModelId', $this->iphone->id)
            ->assertSet('hasPreview', true)
            ->assertSet('caseType', 'softcase');
    }

    public function test_design_with_preview_is_added_with_model_price(): void
    {
        $this->editor()
            ->set('caseType', 'hardcase')
            ->set('color', 'hitam')
            ->call('addToCart', $this->design())
            ->assertHasNoErrors()
            ->assertDispatched('cart-updated');

        $item = app(Cart::class)->items()->sole();
        $this->assertSame('iPhone 17 Pro Max · Hardcase · Hitam', $item['detail']);
        $this->assertSame(3200, $item['price_jpy']);
        $this->assertFalse($item['needs_confirmation']);
        $this->assertSame($this->hardcase->id, $item['refs']['case_type_id']);
        $this->assertSame($this->black->id, $item['refs']['case_color_id']);
        $this->assertSame($this->iphone->id, $item['refs']['phone_model_id']);
        Storage::disk('local')->assertExists($item['refs']['original_path']);
    }

    public function test_rejects_design_whose_image_was_not_uploaded(): void
    {
        Livewire::test(CustomCase::class)
            ->call('addToCart', $this->design())
            ->assertHasErrors('design_image')
            ->assertSee('Gambar belum selesai diunggah.');
    }

    public function test_rejects_uploaded_file_that_is_not_jpg_or_png(): void
    {
        Livewire::test(CustomCase::class)
            ->set('designImage', UploadedFile::fake()->create('desain.gif', 100, 'image/gif'))
            ->call('addToCart', $this->design())
            ->assertHasErrors(['design_image' => 'mimes']);
    }

    public function test_small_zoomed_in_image_is_accepted_because_the_vendor_handles_print_quality(): void
    {
        $this->editor()
            ->call('addToCart', $this->design(['image_width' => 400, 'image_height' => 600, 'zoom' => 4]))
            ->assertHasNoErrors();

        $this->assertCount(1, app(Cart::class)->items());
    }

    public function test_rejects_design_without_both_agreements(): void
    {
        $this->editor()
            ->call('addToCart', $this->design(['agree_rights' => false, 'agree_color' => false]))
            ->assertHasErrors(['agree_rights' => 'accepted', 'agree_color' => 'accepted']);
    }

    public function test_case_type_without_price_for_the_model_cannot_be_ordered(): void
    {
        $this->iphone->customCaseOptions()->where('case_type_id', $this->hardcase->id)->update(['is_available' => false]);

        $this->editor()
            ->assertSee('Tidak tersedia')
            ->set('caseType', 'hardcase')
            ->call('addToCart', $this->design())
            ->assertHasErrors('case_type');
    }

    public function test_searching_lists_matching_models_with_preview_first(): void
    {
        PhoneModel::factory()->create(['name' => 'Galaxy Lama']);
        PhoneModel::factory()->withPreview()->create(['name' => 'Galaxy Baru']);
        PhoneModel::factory()->create(['name' => 'Galaxy Nonaktif', 'is_active' => false]);

        Livewire::test(CustomCase::class)
            ->set('search', 'galaxy')
            ->assertSeeInOrder(['Galaxy Baru', 'Galaxy Lama'])
            ->assertDontSee('Galaxy Nonaktif');
    }

    public function test_model_without_mockup_uses_case_type_default_price_and_needs_confirmation(): void
    {
        $pixel = PhoneModel::factory()->create(['name' => 'Google Pixel 9']);

        $this->editor()
            ->call('selectModel', $pixel->id)
            ->assertSet('hasPreview', false)
            ->set('caseType', 'hardcase')
            ->call('addToCart', $this->design(['image_width' => 1600, 'image_height' => 3200, 'position_note' => 'Wajah di tengah', 'agree_no_preview' => true]))
            ->assertHasNoErrors();

        $item = app(Cart::class)->items()->sole();
        $this->assertSame(3000, $item['price_jpy']);
        $this->assertTrue($item['needs_confirmation']);
        $this->assertNull($item['refs']['custom_case_option_id']);
        $this->assertSame($pixel->id, $item['refs']['phone_model_id']);
    }

    public function test_manually_typed_model_gets_an_approximate_preview_and_needs_the_agreement(): void
    {
        $component = $this->editor()
            ->set('search', 'Fairphone 5')
            ->call('useManualModel')
            ->assertSet('modelName', 'Fairphone 5')
            ->assertSet('phoneModelId', null)
            ->assertSet('hasPreview', false)
            ->assertSee('Tampilan belakang Fairphone 5 (perkiraan).')
            ->assertSeeHtml('viewBox="0 0 73.5 153.5"');

        $component->call('addToCart', $this->design(['image_width' => 1600, 'image_height' => 3200]))
            ->assertHasErrors(['agree_no_preview'])
            ->assertHasNoErrors(['position_note']);

        $component->call('addToCart', $this->design(['image_width' => 1600, 'image_height' => 3200, 'agree_no_preview' => true]))
            ->assertHasNoErrors();

        $item = app(Cart::class)->items()->sole();
        $this->assertTrue($item['needs_confirmation']);
        $this->assertSame('Fairphone 5', $item['refs']['phone_model_text']);
    }

    public function test_model_with_a_drawn_mockup_uses_the_real_phone_proportions(): void
    {
        $samsung = PhoneModel::factory()->withPreview(70, 146)->create(['name' => 'Samsung Galaxy S25', 'slug' => 'samsung-galaxy-s25']);

        Livewire::test(CustomCase::class)
            ->call('selectModel', $samsung->id)
            ->assertSet('printWidthMm', 70.5)
            ->assertSet('printHeightMm', 146.9)
            ->assertSeeHtml('viewBox="0 0 70.5 146.9"')
            ->assertSee('Tampilan belakang Samsung Galaxy S25.');
    }

    public function test_preview_model_without_a_drawn_mockup_gets_a_generic_camera_in_its_print_size(): void
    {
        Livewire::test(CustomCase::class)
            ->assertSet('printWidthMm', 78.0)
            ->assertSeeHtml('viewBox="0 0 78 163"');
    }

    public function test_only_case_type_is_shown_without_a_choice(): void
    {
        $this->hardcase->update(['is_active' => false]);

        Livewire::test(CustomCase::class)
            ->assertSee('satu-satunya jenis yang kami jual')
            ->assertDontSee('Hardcase')
            ->assertSet('caseType', 'softcase');
    }

    public function test_manual_model_requires_at_least_three_characters(): void
    {
        Livewire::test(CustomCase::class)
            ->set('search', 'ab')
            ->call('useManualModel')
            ->assertHasErrors('search')
            ->assertSet('modelName', 'iPhone 17 Pro Max');
    }
}
