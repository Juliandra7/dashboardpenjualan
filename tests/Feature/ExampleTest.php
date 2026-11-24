<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Pahami performa penjualan dengan lebih mudah.');
    }

    public function test_urls_use_https_when_served_through_a_tls_terminating_proxy(): void
    {
        $this->withServerVariables([
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])->get('/')
            ->assertOk()
            ->assertSee('href="https://localhost/login"', false);
    }
}
