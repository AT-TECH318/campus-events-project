<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\Concerns\AuthenticatesWithToken;
use Tests\TestCase;

class FrontendTest extends TestCase
{
    use AuthenticatesWithToken, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
    }

    public function test_pages_are_shells_and_do_not_embed_event_records(): void
    {
        Event::factory()->create(['title' => 'Ndola Career Fair']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Loading events')
            ->assertSee('id="event-filters"', false)
            ->assertDontSee('Ndola Career Fair');

        $this->get('/events/1')
            ->assertOk()
            ->assertSee('Loading event')
            ->assertDontSee('Ndola Career Fair');

        $this->get('/login')->assertOk()->assertSee('type="password"', false);
        $this->get('/register')->assertOk()->assertSee('Confirm password');
        $this->get('/registrations')->assertOk()->assertSee('Loading registrations');
        $this->get('/organizer/events')->assertOk()->assertSee('Create event');
        $this->get('/organizer/events/create')->assertOk()->assertSee('Banner image');
        $this->get('/admin/categories')->assertOk()->assertSee('Add a category');
        $this->get('/admin/users')->assertOk()->assertSee('Loading users');
    }

    public function test_a_browser_session_authorizes_api_requests_without_a_bearer_header(): void
    {
        $user = User::factory()->create();
        $plain = $user->createToken('browser')->plainTextToken;
        $csrf = 'csrf-token-value';

        $this->withSession(['_token' => $csrf])
            ->postJson('/browser-session', ['token' => $plain], ['X-CSRF-TOKEN' => $csrf])
            ->assertOk()
            ->assertJsonStructure(['csrf_token'])
            ->assertDontSee($plain, false);

        $this->withSession(['api_token' => Crypt::encryptString($plain)])
            ->getJson('/api/v1/users/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withSession(['api_token' => Crypt::encryptString($plain)])
            ->postJson('/api/v1/logout')
            ->assertStatus(419);
    }

    public function test_an_invalid_browser_token_is_rejected(): void
    {
        $csrf = 'csrf-token-value';

        $this->withSession(['_token' => $csrf])
            ->postJson('/browser-session', ['token' => 'not-a-real-token'], ['X-CSRF-TOKEN' => $csrf])
            ->assertUnprocessable();

        $this->assertNull(session('api_token'));
    }

    public function test_mine_limits_the_event_list_to_the_authenticated_organizer(): void
    {
        $organizer = User::factory()->organizer()->create();
        $other = User::factory()->organizer()->create();
        Event::factory()->create([
            'title' => 'Owned seminar',
            'organizer_id' => $organizer->id,
        ]);
        Event::factory()->create([
            'title' => 'Someone else',
            'organizer_id' => $other->id,
        ]);

        $this->getJson('/api/v1/events?mine=1')->assertUnauthorized();

        $this->withHeaders($this->bearer($organizer))
            ->getJson('/api/v1/events?mine=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Owned seminar');
    }
}
