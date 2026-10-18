<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesWithToken;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use AuthenticatesWithToken, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->admin = User::factory()->administrator()->create();
    }

    public function test_categories_can_be_listed_and_read_without_a_token(): void
    {
        $category = Category::factory()->create([
            'name' => 'Cultural',
            'slug' => 'cultural',
        ]);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Cultural')
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);

        $this->getJson('/api/v1/categories/'.$category->id)
            ->assertOk()
            ->assertJsonPath('data.slug', 'cultural');

        $this->getJson('/api/v1/categories/999999')->assertNotFound();
    }

    public function test_only_administrators_can_create_update_and_delete_categories(): void
    {
        $student = User::factory()->create();
        $organizer = User::factory()->organizer()->create();

        $this->postJson('/api/v1/categories', [
            'name' => 'Alumni',
        ])->assertUnauthorized();

        $this->withHeaders($this->bearer($student))
            ->postJson('/api/v1/categories', ['name' => 'Alumni'])
            ->assertForbidden();

        $this->withHeaders($this->bearer($organizer))
            ->postJson('/api/v1/categories', ['name' => 'Alumni'])
            ->assertForbidden();

        $this->withHeaders($this->bearer($this->admin))
            ->postJson('/api/v1/categories', [
                'name' => 'Alumni',
                'description' => 'Former students',
                'slug' => 'custom-slug',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);

        $created = $this->withHeaders($this->bearer($this->admin))
            ->postJson('/api/v1/categories', [
                'name' => 'Alumni',
                'description' => 'Former students',
            ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'alumni');

        $categoryId = $created->json('data.id');

        $this->withHeaders($this->bearer($organizer))
            ->putJson('/api/v1/categories/'.$categoryId, [
                'name' => 'Alumni Network',
            ])
            ->assertForbidden();

        $this->withHeaders($this->bearer($this->admin))
            ->putJson('/api/v1/categories/'.$categoryId, [
                'description' => 'Missing the name',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->withHeaders($this->bearer($this->admin))
            ->putJson('/api/v1/categories/'.$categoryId, [
                'name' => 'Alumni Network',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Alumni Network')
            ->assertJsonPath('data.slug', 'alumni-network');

        $this->withHeaders($this->bearer($this->admin))
            ->patchJson('/api/v1/categories/'.$categoryId, [
                'description' => 'Updated description',
            ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Updated description')
            ->assertJsonPath('data.name', 'Alumni Network');

        $this->withHeaders($this->bearer($this->admin))
            ->postJson('/api/v1/categories', [
                'name' => 'Alumni Network',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->withHeaders($this->bearer($this->admin))
            ->deleteJson('/api/v1/categories/'.$categoryId)
            ->assertNoContent();

        $this->getJson('/api/v1/categories/'.$categoryId)->assertNotFound();
    }

    public function test_a_category_used_by_an_event_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Event::factory()->create(['category_id' => $category->id]);

        $this->withHeaders($this->bearer($this->admin))
            ->deleteJson('/api/v1/categories/'.$category->id)
            ->assertStatus(409);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_category_page_size_is_capped(): void
    {
        Category::factory()->count(2)->create();

        $this->getJson('/api/v1/categories?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson('/api/v1/categories?per_page=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }
}
