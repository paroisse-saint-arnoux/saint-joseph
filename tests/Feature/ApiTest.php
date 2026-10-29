<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_a_token(): void
    {
        $this->getJson('/api/articles')->assertUnauthorized();
    }

    public function test_crud_on_articles(): void
    {
        Passport::actingAs($user = User::factory()->create());

        $slug = $this->postJson('/api/articles', [
            'title' => 'Article HTML', 'category' => 'equipe', 'format' => 'html', 'body' => '<p>Bonjour</p>',
        ])->assertCreated()->assertJsonPath('data.status', 'draft')->json('data.slug');

        $this->getJson("/api/articles/{$slug}")->assertOk()->assertJsonPath('data.body', '<p>Bonjour</p>');

        $this->patchJson("/api/articles/{$slug}", ['status' => 'published'])
            ->assertOk()
            ->assertJsonPath('data.status', 'published');
        $this->assertNotNull(Article::firstWhere('slug', $slug)->published_at);
        $this->assertSame($user->id, Article::firstWhere('slug', $slug)->author_id);

        $this->postJson('/api/articles', ['title' => 'x', 'category' => 'inconnue'])->assertUnprocessable();

        $this->deleteJson("/api/articles/{$slug}")->assertNoContent();
        $this->assertDatabaseMissing('articles', ['slug' => $slug]);
    }

    public function test_media_upload_converts_to_webp(): void
    {
        Storage::fake('public');
        Passport::actingAs(User::factory()->create());

        $path = $this->post('/api/media', ['file' => UploadedFile::fake()->image('photo.jpg', 2400, 1600)], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('path');

        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(1920, getimagesizefromstring(Storage::disk('public')->get($path))[0]);
    }
}
