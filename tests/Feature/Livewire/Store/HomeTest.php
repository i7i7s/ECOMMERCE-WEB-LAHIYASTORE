<?php

namespace Tests\Feature\Livewire\Store;

use App\Models\Category;
use App\Models\PreorderBatch;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_landing_page_shows_tear_reveal_and_the_open_batch(): void
    {
        PreorderBatch::factory()->create(['code' => 'PO-2026-W40', 'cutoff_at' => CarbonImmutable::parse('2026-10-04 23:59', 'Asia/Tokyo')->utc()]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('data-tear-reveal', false);
        $response->assertSee('Batch PO-2026-W40 sedang dibuka');
        $response->assertSee('Estimasi tiba 18–25 Okt 2026');
    }

    public function test_hero_reveals_phones_in_lahiya_cases_behind_the_logo_paper(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-tear-scene', false)
            ->assertSee('muncul HP dengan casing custom')
            ->assertDontSee('harimau')
            ->assertSee('favicon.svg', false);
    }

    public function test_lists_only_active_categories(): void
    {
        Category::factory()->create(['name' => 'Jaket']);
        Category::factory()->create(['name' => 'Mainan', 'is_active' => false]);

        $this->get(route('home'))
            ->assertSee('Jaket')
            ->assertDontSee('Mainan');
    }
}
