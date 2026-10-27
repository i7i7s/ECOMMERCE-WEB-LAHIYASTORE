<?php

namespace Tests\Feature\Providers;

use Illuminate\Foundation\Console\ServeCommand;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_artisan_serve_keeps_the_temp_directory_so_uploads_work_on_windows(): void
    {
        $this->assertContains('TEMP', ServeCommand::$passthroughVariables);
        $this->assertContains('TMP', ServeCommand::$passthroughVariables);
    }
}
