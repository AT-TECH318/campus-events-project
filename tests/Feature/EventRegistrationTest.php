<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesWithToken;
use Tests\TestCase;

class EventRegistrationTest extends TestCase
{
    use AuthenticatesWithToken, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
    }

    public function test_an_authenticated_student_can_register_and_see_the_registration(): void
    {
        $student = User::factory()->create();
        $event = Event::factory()->create(['maximum_capacity' => 5, 'title' => 'Open Seminar']);

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertCreated()
            ->assertJsonPath('message', 'Registration successful.')
            ->assertJsonPath('data.user_id', $student->id)
            ->assertJsonPath('data.event_id', $event->id)
            ->assertJsonPath('data.event.title', 'Open Seminar');

        $this->assertSame(1, $event->registrations()->count());
        $this->assertSame(4, $event->fresh()->remainingCapacity());

        $this->withHeaders($this->bearer($student))
            ->getJson('/api/v1/users/me/registrations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_id', $event->id);

        $other = User::factory()->create();

        $this->withHeaders($this->bearer($other))
            ->getJson('/api/v1/users/me/registrations')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_student_cannot_register_twice(): void
    {
        $student = User::factory()->create();
        $event = Event::factory()->create(['maximum_capacity' => 5]);

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertCreated();

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertStatus(409)
            ->assertJsonPath('reason', 'already_registered');

        $this->assertSame(1, $event->registrations()->count());
    }

    public function test_registration_stops_when_the_event_is_full_and_a_cancellation_frees_the_seat(): void
    {
        $event = Event::factory()->create(['maximum_capacity' => 1, 'title' => 'One Seat']);
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->withHeaders($this->bearer($first))
            ->postJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertCreated();

        $this->assertSame(0, $event->fresh()->remainingCapacity());

        $this->withHeaders($this->bearer($second))
            ->postJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertStatus(422)
            ->assertJsonPath('reason', 'at_capacity');

        $this->assertSame(1, $event->registrations()->count());

        $this->withHeaders($this->bearer($first))
            ->deleteJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertOk()
            ->assertJsonPath('message', 'Registration cancelled.');

        $this->assertSame(0, $event->registrations()->count());
        $this->assertSame(1, $event->fresh()->remainingCapacity());

        $this->withHeaders($this->bearer($second))
            ->postJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertCreated();

        $this->assertSame(1, $event->registrations()->count());
        $this->assertSame(0, $event->fresh()->remainingCapacity());
        $this->assertSame($second->id, (int) $event->registrations()->value('user_id'));
    }

    public function test_registration_rejects_a_passed_deadline_and_events_that_are_not_published(): void
    {
        $student = User::factory()->create();
        $closed = Event::factory()->create([
            'registration_deadline' => now()->subMinute(),
        ]);
        $draft = Event::factory()->create([
            'status' => EventStatus::Draft,
        ]);
        $cancelled = Event::factory()->create([
            'status' => EventStatus::Cancelled,
        ]);

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/events/'.$closed->id.'/registrations')
            ->assertStatus(422)
            ->assertJsonPath('reason', 'deadline_passed');

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/events/'.$draft->id.'/registrations')
            ->assertStatus(422)
            ->assertJsonPath('reason', 'not_open');

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/events/'.$cancelled->id.'/registrations')
            ->assertStatus(422)
            ->assertJsonPath('reason', 'not_open');

        $this->assertSame(0, $closed->registrations()->count());
        $this->assertSame(0, $draft->registrations()->count());
        $this->assertSame(0, $cancelled->registrations()->count());
    }

    public function test_cancellation_removes_only_the_callers_registration(): void
    {
        $event = Event::factory()->create(['maximum_capacity' => 2]);
        $student = User::factory()->create();
        $other = User::factory()->create();

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertCreated();

        $this->withHeaders($this->bearer($other))
            ->postJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertCreated();

        $this->withHeaders($this->bearer($student))
            ->deleteJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertOk();

        $this->assertSame(1, $event->registrations()->count());
        $this->assertSame(1, $event->fresh()->remainingCapacity());
        $this->assertTrue($event->registrations()->where('user_id', $other->id)->exists());

        $this->withHeaders($this->bearer($student))
            ->getJson('/api/v1/users/me/registrations')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withHeaders($this->bearer($student))
            ->deleteJson('/api/v1/events/'.$event->id.'/registrations')
            ->assertNotFound()
            ->assertJsonPath('reason', 'not_registered');
    }

    public function test_registration_endpoints_require_authentication(): void
    {
        $event = Event::factory()->create();

        $this->postJson('/api/v1/events/'.$event->id.'/registrations')->assertUnauthorized();
        $this->deleteJson('/api/v1/events/'.$event->id.'/registrations')->assertUnauthorized();
        $this->getJson('/api/v1/users/me/registrations')->assertUnauthorized();
    }
}
