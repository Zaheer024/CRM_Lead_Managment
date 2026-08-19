<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    public function test_login_returns_token_for_active_user(): void
    {
        $this->createAdmin(['email' => 'admin@example.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'roles']]);
    }

    public function test_login_rejected_for_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->createAdmin(['email' => 'admin@example.com', 'status_id' => $this->statusId(User::STATUS_INACTIVE)]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403);
    }

    public function test_protected_endpoints_require_authentication(): void
    {
        $this->getJson('/api/leads')->assertUnauthorized();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = $this->createAdmin();

        Sanctum::actingAs($user);

        $this->getJson('/api/me')->assertOk()->assertJson(['email' => $user->email]);
    }

    public function test_revoked_token_gets_401_json_instead_of_error_page(): void
    {
        $user = $this->createAdmin();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/dashboard')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }
}
