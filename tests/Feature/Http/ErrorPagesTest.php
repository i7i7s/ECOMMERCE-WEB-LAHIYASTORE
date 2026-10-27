<?php

namespace Tests\Feature\Http;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unknown_page_shows_the_branded_404(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Kembali ke beranda')
            ->assertSee('favicon.svg', false);
    }

    public function test_missing_admin_page_links_back_to_the_admin_dashboard(): void
    {
        $this->get('/admin/tidak-ada')
            ->assertNotFound()
            ->assertSee('Ke dashboard admin');
    }

    /**
     * Error views must render without the database or the Vite build, so they work when those fail.
     */
    public function test_server_and_maintenance_pages_render_on_their_own(): void
    {
        foreach ([401, 403, 419, 429, 500, 503] as $status) {
            $html = view("errors.{$status}", ['exception' => new HttpException($status)])->render();

            $this->assertStringContainsString('lahiya', $html, "errors.{$status}");
            $this->assertStringNotContainsString('build/assets', $html, "errors.{$status}");
        }

        $this->assertStringContainsString('Gagal memuat halaman', view('errors.5xx', ['exception' => new HttpException(502)])->render());
    }

    public function test_store_pages_include_the_offline_banner(): void
    {
        $this->get(route('track'))
            ->assertOk()
            ->assertSee('Kamu sedang offline');
    }

    public function test_offline_fallback_files_are_published(): void
    {
        $this->assertFileExists(public_path('offline.html'));
        $this->assertStringContainsString("event.request.mode !== 'navigate'", file_get_contents(public_path('sw.js')));
    }
}
