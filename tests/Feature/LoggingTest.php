<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

class LoggingTest extends TestCase
{
    use RefreshDatabase, CreatesShopData;

    public function test_request_is_logged_without_password_and_token(): void
    {
        $this->makeUser('customer', ['email' => 'log@example.com', 'password' => 'super-secret-pass']);

        Log::spy();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'log@example.com',
            'password' => 'super-secret-pass',
        ])->assertOk();

        Log::shouldHaveReceived('log')
            ->withArgs(function ($level, $message, $context = []) {
                return $message === 'API request'
                    && ($context['method'] ?? null) === 'POST'
                    && ($context['status'] ?? null) === 200
                    && ! str_contains(json_encode($context), 'super-secret-pass');
            })
            ->atLeast()->once();
    }
}
