<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesWithToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use AuthenticatesWithToken, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
    }

    public function test_registration_issues_a_token_and_assigns_the_student_role(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Chipo Banda',
            'email' => 'chipo@campus.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('data.role.slug', Role::SLUG_STUDENT)
            ->assertJsonPath('data.email', 'chipo@campus.test');

        $this->assertArrayNotHasKey('password', $response->json('data'));
        $this->assertNotEmpty($response->json('token'));

        $this->withHeader('Authorization', 'Bearer '.$response->json('token'))
            ->getJson('/api/v1/users/me')
            ->assertOk()
            ->assertJsonPath('data.role.slug', Role::SLUG_STUDENT);

        $this->assertDatabaseHas('users', [
            'email' => 'chipo@campus.test',
            'role_id' => Role::query()->where('slug', Role::SLUG_STUDENT)->value('id'),
        ]);
    }

    public function test_registration_rejects_a_submitted_role(): void
    {
        $administratorId = Role::query()->where('slug', Role::SLUG_ADMINISTRATOR)->value('id');

        $this->postJson('/api/v1/register', [
            'name' => 'Ada Banda',
            'email' => 'ada@campus.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => Role::SLUG_ADMINISTRATOR,
            'role_id' => $administratorId,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['role', 'role_id']);

        $this->assertDatabaseMissing('users', ['email' => 'ada@campus.test']);
    }

    public function test_login_issues_a_bearer_token(): void
    {
        $user = User::factory()->create([
            'email' => 'login@campus.test',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'login@campus.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('data.email', $user->email);

        $this->withHeader('Authorization', 'Bearer '.$response->json('token'))
            ->getJson('/api/v1/users/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@campus.test',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'login@campus.test',
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'The provided credentials are incorrect.');

        $this->postJson('/api/v1/login', [
            'email' => 'missing@campus.test',
            'password' => 'password',
        ])->assertUnauthorized();
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('current')->plainTextToken;
        $other = $user->createToken('other')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$current)
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');

        $this->withHeader('Authorization', 'Bearer '.$current)
            ->getJson('/api/v1/users/me')
            ->assertUnauthorized();

        $this->withHeader('Authorization', 'Bearer '.$other)
            ->getJson('/api/v1/users/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/users/me')->assertUnauthorized();
    }
}
