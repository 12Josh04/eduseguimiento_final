<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeccionTest extends TestCase
{
    public function test_sections_page_requires_authentication()
    {
        $response = $this->get('/secciones');

        $response->assertStatus(302);
    }
}
