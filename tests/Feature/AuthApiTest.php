<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase, CreatesShopData;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Иван',
            'email' => 'ivan@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user', 'token'])
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', ['email' => 'ivan@example.com', 'role' => 'customer']);
    }

    public function test_register_ignores_role_from_request(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Хакер',
            'email' => 'hacker@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'admin',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'hacker@example.com', 'role' => 'customer']);
    }

    public function test_register_validation_errors(): void
    {
        $this->makeUser('customer', ['email' => 'busy@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Иван',
            'email' => 'busy@example.com',
            'password' => '123',
            'password_confirmation' => '456',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Ошибка валидации')
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_user_can_login_and_gets_token(): void
    {
        $this->makeUser('customer', ['email' => 'login@example.com', 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ])->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        $this->makeUser('customer', ['email' => 'login@example.com', 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Неверный email или пароль');
    }

    public function test_me_requires_token(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Требуется авторизация');

        $this->withToken('invalid-token')
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_me_returns_current_user(): void
    {
        $user = $this->signIn('manager');

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_logout_deletes_current_token(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
