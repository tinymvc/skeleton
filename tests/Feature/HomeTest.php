<?php

namespace Tests\Feature;

use Tests\TestCase;

final class HomeTest extends TestCase
{
    public function test_home_page_is_available(): void
    {
        $this->get('/')->assertOk()->assertSee('TinyMVC');
    }

    public function test_missing_pages_return_not_found(): void
    {
        $this->getJson('/missing-page')->assertStatus(404);
    }
}
