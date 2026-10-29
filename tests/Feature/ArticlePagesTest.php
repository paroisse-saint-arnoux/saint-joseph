<?php

namespace Tests\Feature;

use App\Enums\Category;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_article_is_visible_and_listed(): void
    {
        $article = Article::factory()->create(['title' => 'Premier article']);

        $this->get($article->url())->assertOk()->assertSee('Premier article')->assertDontSee('noindex');
        $this->get('/le-projet')->assertOk()->assertSee('Premier article');
        $this->get('/le-projet/projet')->assertOk()->assertSee('Premier article');
        $this->get('/le-projet/budget')->assertOk()->assertDontSee('Premier article');
    }

    public function test_draft_is_only_reachable_with_preview_token(): void
    {
        $draft = Article::factory()->draft()->create(['title' => 'Brouillon secret']);

        $this->get($draft->url())->assertNotFound();
        $this->get($draft->url().'?apercu=mauvais')->assertNotFound();
        $this->get($draft->previewUrl())->assertOk()->assertSee('Brouillon secret')->assertSee('noindex');
        $this->get('/le-projet')->assertDontSee('Brouillon secret');
        $this->get('/sitemap.xml')->assertOk()->assertDontSee($draft->slug);
    }

    public function test_logged_in_editor_can_see_drafts(): void
    {
        $draft = Article::factory()->draft()->create();

        $this->actingAs(User::factory()->create())->get($draft->url())->assertOk();
    }

    public function test_scheduled_article_is_hidden_until_its_date(): void
    {
        $article = Article::factory()->create(['published_at' => now()->addDay()]);

        $this->get($article->url())->assertNotFound();
    }

    public function test_wrong_category_redirects_to_canonical_url(): void
    {
        $article = Article::factory()->create(['category' => Category::Budget]);

        $this->get('/le-projet/equipe/'.$article->slug)->assertRedirect($article->url());
    }

    public function test_pinned_articles_come_first(): void
    {
        Article::factory()->create(['title' => 'Récent', 'published_at' => now()->subHour()]);
        Article::factory()->create(['title' => 'Ancien épinglé', 'published_at' => now()->subYear(), 'is_pinned' => true]);

        $this->get('/le-projet')->assertSeeInOrder(['Ancien épinglé', 'Récent']);
    }

    public function test_infinite_scroll_returns_json_pages(): void
    {
        Article::factory()->count(12)->create();

        $this->getJson('/le-projet?page=2', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonStructure(['html', 'next'])
            ->assertJsonPath('next', null);
    }

    public function test_slugs_are_unique(): void
    {
        $a = Article::factory()->create(['title' => 'Même titre']);
        $b = Article::factory()->create(['title' => 'Même titre']);

        $this->assertSame('meme-titre', $a->slug);
        $this->assertSame('meme-titre-2', $b->slug);
    }

    public function test_markdown_youtube_links_become_embeds(): void
    {
        $article = Article::factory()->create(['body' => "Intro\n\nhttps://youtu.be/dQw4w9WgXcQ\n"]);

        $this->get($article->url())->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
    }

    public function test_home_page_renders_without_settings(): void
    {
        $this->get('/')->assertOk();
    }
}
