<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesWithToken;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use AuthenticatesWithToken, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
    }

    public function test_only_administrators_can_list_users_and_change_roles(): void
    {
        $admin = User::factory()->administrator()->create();
        $organizer = User::factory()->organizer()->create();
        $student = User::factory()->create();

        $this->getJson('/api/v1/users')->assertUnauthorized();

        $this->withHeaders($this->bearer($student))
            ->getJson('/api/v1/users')
            ->assertForbidden();

        $this->withHeaders($this->bearer($organizer))
            ->getJson('/api/v1/users')
            ->assertForbidden();

        $this->withHeaders($this->bearer($admin))
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonFragment(['email' => $student->email]);

        $this->withHeaders($this->bearer($student))
            ->patchJson('/api/v1/users/'.$student->id.'/role', [
                'role' => Role::SLUG_ADMINISTRATOR,
            ])->assertForbidden();

        $this->assertTrue($student->fresh()->hasRole(Role::SLUG_STUDENT));

        $this->withHeaders($this->bearer($organizer))
            ->patchJson('/api/v1/users/'.$student->id.'/role', [
                'role' => Role::SLUG_ORGANIZER,
            ])->assertForbidden();

        $this->withHeaders($this->bearer($admin))
            ->patchJson('/api/v1/users/'.$student->id.'/role', [
                'role' => Role::SLUG_ORGANIZER,
            ])->assertOk()
            ->assertJsonPath('data.role.slug', Role::SLUG_ORGANIZER);

        $this->assertTrue($student->fresh()->hasRole(Role::SLUG_ORGANIZER));
    }

    public function test_the_last_administrator_cannot_be_demoted(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->withHeaders($this->bearer($admin))
            ->patchJson('/api/v1/users/'.$admin->id.'/role', [
                'role' => Role::SLUG_STUDENT,
            ])->assertUnprocessable();

        $this->assertTrue($admin->fresh()->hasRole(Role::SLUG_ADMINISTRATOR));
    }

    public function test_organizers_can_manage_only_their_own_events(): void
    {
        $owner = User::factory()->organizer()->create();
        $other = User::factory()->organizer()->create();
        $student = User::factory()->create();
        $admin = User::factory()->administrator()->create();
        $event = Event::factory()->create([
            'organizer_id' => $owner->id,
            'title' => 'Owned seminar',
        ]);

        $this->patchJson('/api/v1/events/'.$event->id, [
            'title' => 'Anonymous edit',
        ])->assertUnauthorized();

        $this->withHeaders($this->bearer($student))
            ->patchJson('/api/v1/events/'.$event->id, [
                'title' => 'Student edit',
            ])->assertForbidden();

        $this->withHeaders($this->bearer($other))
            ->patchJson('/api/v1/events/'.$event->id, [
                'title' => 'Other organizer edit',
            ])->assertForbidden();

        $this->withHeaders($this->bearer($other))
            ->deleteJson('/api/v1/events/'.$event->id)
            ->assertForbidden();

        $this->withHeaders($this->bearer($owner))
            ->patchJson('/api/v1/events/'.$event->id, [
                'title' => 'Owner edit',
                'organizer_id' => $other->id,
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['organizer_id']);

        $this->assertSame($owner->id, (int) $event->fresh()->organizer_id);
        $this->assertSame('Owned seminar', $event->fresh()->title);

        $this->withHeaders($this->bearer($owner))
            ->patchJson('/api/v1/events/'.$event->id, [
                'title' => 'Owner edit',
            ])->assertOk()
            ->assertJsonPath('data.title', 'Owner edit');

        $this->withHeaders($this->bearer($admin))
            ->patchJson('/api/v1/events/'.$event->id, [
                'title' => 'Admin edit',
            ])->assertOk()
            ->assertJsonPath('data.title', 'Admin edit');

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/events', [
                'title' => 'Student event',
            ])->assertForbidden();

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Admin edit',
            'organizer_id' => $owner->id,
        ]);
    }

    public function test_only_administrators_can_manage_categories(): void
    {
        $admin = User::factory()->administrator()->create();
        $student = User::factory()->create();
        $organizer = User::factory()->organizer()->create();

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/categories', [
                'name' => 'Alumni',
                'description' => 'Alumni events',
            ])->assertForbidden();

        $this->withHeaders($this->bearer($organizer))
            ->postJson('/api/v1/categories', [
                'name' => 'Alumni',
                'description' => 'Alumni events',
            ])->assertForbidden();

        $this->withHeaders($this->bearer($admin))
            ->postJson('/api/v1/categories', [
                'name' => 'Alumni',
                'description' => 'Alumni events',
            ])->assertCreated()
            ->assertJsonPath('data.slug', 'alumni');

        $this->assertDatabaseHas('categories', ['name' => 'Alumni', 'slug' => 'alumni']);
    }

    public function test_authenticated_users_can_browse_and_register_for_events(): void
    {
        $student = User::factory()->create();
        $event = Event::factory()->create(['maximum_capacity' => 5]);

        $this->getJson('/api/v1/events')->assertOk();

        $this->postJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertUnauthorized();

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertCreated()
            ->assertJsonPath('data.user_id', $student->id)
            ->assertJsonPath('data.event_id', $event->id);
    }
}
