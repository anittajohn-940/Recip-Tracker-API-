<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecipeApiTest extends TestCase
{
    use RefreshDatabase; // Ensures each test starts with a fresh DB

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('TestToken')->plainTextToken;
    }

    /** @test */
    public function it_registers_a_user()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user', 'token']);
    }

    /** @test */
    public function it_logs_in_a_user()
    {
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'password', // Default factory password
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    /** @test */
    public function it_creates_a_recipe()
    {
        $response = $this->postJson('/api/recipes', [
            'name' => 'Egg Masala',
            'ingredients' => 'egg, onion, garlic',
            'prep_time' => 10,
            'cook_time' => 20,
            'difficulty' => 'easy',
            'description' => 'Delicious egg recipe',
        ], [
            'Authorization' => "Bearer {$this->token}"
        ]);

        $response->assertStatus(201)
            ->assertJson(['name' => 'Egg Masala']);
    }

    /** @test */
    public function it_fetches_all_recipes()
    {
        Recipe::factory()->count(3)->create();

        $response = $this->getJson('/api/recipes', [
            'Authorization' => "Bearer {$this->token}"
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /** @test */
    public function it_fetches_a_single_recipe()
    {
        $recipe = Recipe::factory()->create();

        $response = $this->getJson("/api/recipes/{$recipe->id}", [
            'Authorization' => "Bearer {$this->token}"
        ]);

        $response->assertStatus(200)
            ->assertJson(['id' => $recipe->id]);
    }

    public function it_updates_a_recipe()
    {
        $recipe = Recipe::factory()->create();

        $response = $this->putJson("/api/recipes/{$recipe->id}", [
            'name' => 'Updated Recipe Name',
        ], [
            'Authorization' => "Bearer {$this->token}"
        ]);

        $response->assertStatus(200)
            ->assertJson(['name' => 'Updated Recipe Name']);
    }

    public function it_deletes_a_recipe()
    {
        $recipe = Recipe::factory()->create();

        $response = $this->deleteJson("/api/recipes/{$recipe->id}", [], [
            'Authorization' => "Bearer {$this->token}"
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Recipe deleted successfully']);
    }

    public function it_performs_advanced_search()
    {
        Recipe::factory()->create([
            'ingredients' => 'potato, onion, ginger, cumin',
            'prep_time' => 10,
            'cook_time' => 15,
        ]);

        $response = $this->getJson('/api/recipes/search?ingredients=potato,onion&time=20-30', [
            'Authorization' => "Bearer {$this->token}"
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }
}