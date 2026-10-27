<?php

namespace Tests\Feature\Actions\PhoneModels;

use App\Actions\PhoneModels\SyncPhoneModels;
use App\Enums\PhoneModelSource;
use App\Models\CaseType;
use App\Models\PhoneBrand;
use App\Models\PhoneModel;
use App\Support\PhoneCatalog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SyncPhoneModelsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_adds_brands_and_models_from_the_catalog(): void
    {
        $result = app(SyncPhoneModels::class)->handle(['Samsung' => ['Samsung Galaxy S26+', 'Samsung Galaxy S26']]);

        $this->assertSame(['created' => 2, 'updated' => 0], $result);
        $model = PhoneModel::where('slug', 'samsung-galaxy-s26-plus')->sole();
        $this->assertSame('Samsung Galaxy S26+', $model->name);
        $this->assertSame(PhoneModelSource::Katalog, $model->source);
        $this->assertTrue($model->is_active);
        $this->assertFalse($model->has_preview);
        $this->assertSame('Samsung', $model->brand->name);
    }

    public function test_running_again_adds_nothing_and_never_deletes(): void
    {
        $extra = PhoneModel::factory()->create(['name' => 'Fairphone 5']);
        app(SyncPhoneModels::class)->handle(['Google' => ['Google Pixel 11']]);

        $result = app(SyncPhoneModels::class)->handle(['Google' => ['Google Pixel 11']]);

        $this->assertSame(['created' => 0, 'updated' => 0], $result);
        $this->assertSame(1, PhoneModel::where('slug', 'google-pixel-11')->count());
        $this->assertModelExists($extra);
    }

    public function test_models_created_by_the_admin_are_left_alone(): void
    {
        $brand = PhoneBrand::factory()->create(['name' => 'Apple']);
        $manual = PhoneModel::factory()->for($brand, 'brand')->withPreview()->create(['name' => 'iPhone 17 Pro Max', 'slug' => 'iphone-17-pro-max']);

        app(SyncPhoneModels::class)->handle(['Apple' => ['iPhone 17 Pro Max', 'iPhone Duo']]);

        $this->assertSame(PhoneModelSource::Manual, $manual->refresh()->source);
        $this->assertTrue($manual->has_preview);
        $this->assertSame(2, PhoneModel::count());
    }

    public function test_bundled_catalog_has_unique_slugs(): void
    {
        app(SyncPhoneModels::class)->handle();

        $this->assertSame(collect(PhoneCatalog::MODELS)->flatten()->count(), PhoneModel::count());
    }

    public function test_models_with_a_drawn_mockup_get_the_preview_and_case_prices(): void
    {
        $softcase = CaseType::factory()->create(['default_price_jpy' => 2800]);

        app(SyncPhoneModels::class)->handle(['Apple' => ['iPhone 18 Pro Max', 'iPhone 12']]);

        $proMax = PhoneModel::where('slug', 'iphone-18-pro-max')->sole();
        $this->assertTrue($proMax->has_preview);
        $this->assertSame(163.4, $proMax->print_height_mm);
        $this->assertSame(2800, $proMax->customCaseOptions()->where('case_type_id', $softcase->id)->value('price_jpy'));
        $this->assertFalse(PhoneModel::where('slug', 'iphone-12')->sole()->has_preview);
    }

    public function test_command_syncs_the_bundled_catalog(): void
    {
        $this->artisan('phones:sync')->assertSuccessful();

        $this->assertTrue(PhoneModel::where('name', 'iPhone 18 Pro Max')->exists());
    }
}
