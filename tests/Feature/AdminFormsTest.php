<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\Role;
use App\Filament\Pages\ManageHome;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Models\Article;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
    }

    public function test_status_toggle_publishes_and_unpublishes(): void
    {
        $article = Article::factory()->draft()->create();

        Livewire::test(EditArticle::class, ['record' => $article->id])
            ->assertSchemaStateSet(['is_published' => false])
            ->fillForm(['is_published' => true])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(ArticleStatus::Published, $article->refresh()->status);
        $this->assertNotNull($article->published_at);

        Livewire::test(EditArticle::class, ['record' => $article->id])
            ->assertSchemaStateSet(['is_published' => true])
            ->fillForm(['is_published' => false])
            ->call('save');
        $this->assertSame(ArticleStatus::Draft, $article->refresh()->status);
    }

    public function test_settings_pages_remember_saved_data_for_the_unsaved_changes_bar(): void
    {
        Setting::put('home', [
            'hero' => ['title' => 'Bâtir'], 'decouvrir' => ['title' => 'Découvrir'], 'projet' => ['title' => 'Le projet'],
            'avancement' => ['title' => 'Avancement'], 'collecte' => ['raised' => 1000, 'goal' => 6000000, 'title' => 'Faites monter le clocher.'],
        ]);

        $page = Livewire::test(ManageHome::class);
        $hash = fn () => md5(str_replace('\\', '', json_encode($page->get('data'), JSON_UNESCAPED_UNICODE)));
        $this->assertSame($hash(), $page->get('savedDataHash'));

        $page->set('data.home.collecte.title', 'Montons le clocher.');
        $this->assertNotSame($hash(), $page->get('savedDataHash'));

        $page->call('save')->assertHasNoFormErrors();
        $this->assertSame($hash(), $page->get('savedDataHash'));
        $this->assertSame('Montons le clocher.', Setting::get('home.collecte.title'));
    }
}
