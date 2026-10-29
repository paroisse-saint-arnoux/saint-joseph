<?php

namespace Tests\Feature;

use App\Mcp\Servers\SiteServer;
use App\Mcp\Tools\CreateArticle;
use App\Mcp\Tools\GetPage;
use App\Mcp\Tools\UpdateArticle;
use App\Mcp\Tools\UpdateHomeSection;
use App\Models\Article;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class McpTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create();
    }

    public function test_create_article_is_a_draft_with_author_and_admin_link(): void
    {
        SiteServer::actingAs($this->user)->tool(CreateArticle::class, [
            'title' => 'Bénédiction du terrain',
            'category' => 'agenda',
            'excerpt' => 'Une belle journée.',
            'body' => "## Le programme\n\nMesse puis pique-nique.",
        ])->assertOk()->assertSee('/admin/articles/');

        $article = Article::firstWhere('slug', 'benediction-du-terrain');
        $this->assertSame('draft', $article->status->value);
        $this->assertSame($this->user->id, $article->author_id);
    }

    public function test_cover_image_requires_a_description(): void
    {
        SiteServer::actingAs($this->user)->tool(CreateArticle::class, [
            'title' => 'Sans alt', 'category' => 'projet', 'og_image' => 'articles/x.webp',
        ])->assertHasErrors();
    }

    public function test_get_and_update_article_from_its_public_url(): void
    {
        $article = Article::factory()->create(['title' => 'Le budget', 'category' => 'budget', 'body' => 'Ancien texte']);

        SiteServer::actingAs($this->user)->tool(GetPage::class, ['page' => $article->url()])
            ->assertOk()->assertSee('Ancien texte');

        SiteServer::actingAs($this->user)->tool(UpdateArticle::class, ['article' => $article->url(), 'body' => 'Nouveau texte'])
            ->assertOk();

        $this->assertSame('Nouveau texte', $article->refresh()->body);
        $this->assertSame('published', $article->status->value);
    }

    public function test_home_and_legal_pages(): void
    {
        Setting::put('home', ['collecte' => ['raised' => 1000, 'goal' => 6000000, 'title' => 'Faites monter le clocher.']]);
        Setting::put('legal', ['mentions' => 'Mentions', 'privacy' => 'Confidentialité']);

        SiteServer::actingAs($this->user)->tool(GetPage::class, ['page' => url('/')])->assertSee('Faites monter le clocher.');
        SiteServer::actingAs($this->user)->tool(GetPage::class, ['page' => url('/mentions-legales')])->assertSee('Mentions');

        SiteServer::actingAs($this->user)->tool(UpdateHomeSection::class, ['section' => 'collecte', 'content' => ['raised' => 1550000]])
            ->assertOk();
        $this->assertSame(1550000, Setting::get('home.collecte.raised'));
        $this->assertSame('Faites monter le clocher.', Setting::get('home.collecte.title'));

        SiteServer::actingAs($this->user)->tool(UpdateHomeSection::class, ['section' => 'collecte', 'content' => ['inconnu' => 'x']])
            ->assertHasErrors();
    }

    public function test_mcp_endpoint_requires_oauth_and_advertises_it(): void
    {
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertUnauthorized();
        $this->getJson('/.well-known/oauth-protected-resource/mcp')->assertOk();
        $this->getJson('/.well-known/oauth-authorization-server')->assertOk()->assertJsonPath('registration_endpoint', url('/oauth/register'));
    }

    public function test_only_claude_can_register_as_oauth_client(): void
    {
        $this->postJson('/oauth/register', ['client_name' => 'Claude', 'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback']])->assertCreated();
        $this->postJson('/oauth/register', ['client_name' => 'Pirate', 'redirect_uris' => ['https://evil.example/callback']])->assertStatus(400);
    }

}
