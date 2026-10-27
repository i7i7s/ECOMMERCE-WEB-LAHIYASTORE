<?php

namespace Tests\Feature\Models;

use App\Models\PhoneModel;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PhoneModelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_model_with_mockup_and_print_size_has_preview(): void
    {
        $this->assertTrue(PhoneModel::factory()->withPreview()->create()->has_preview);
    }

    public function test_model_without_mockup_has_no_preview(): void
    {
        $this->assertFalse(PhoneModel::factory()->create()->has_preview);
    }

    public function test_mockup_without_print_size_has_no_preview(): void
    {
        $model = PhoneModel::factory()->withPreview()->create(['print_height_mm' => null]);

        $this->assertFalse($model->has_preview);
    }
}
