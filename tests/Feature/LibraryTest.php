<?php

namespace Tests\Feature;

use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\LibraryImages\Pages\EditLibraryImage;
use App\Filament\Resources\LibraryImages\Pages\ListLibraryImages;
use App\Mcp\Servers\SiteServer;
use App\Mcp\Tools\ImportImageFromUrl;
use App\Mcp\Tools\PrepareArticleImage;
use App\Mcp\Tools\SearchLibrary;
use App\Mcp\Tools\UpdateLibraryImage;
use App\Models\Article;
use App\Models\LibraryImage;
use App\Models\LibraryUsage;
use App\Models\Setting;
use App\Enums\Role;
use App\Models\User;
use App\Support\ImageInfo;
use App\Support\Images;
use App\Support\Library;
use App\Support\LibraryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        $this->user = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($this->user);
    }

    public function test_same_image_in_another_format_is_not_added_twice(): void
    {
        $first = Library::add($this->photo(1, 1200, 800, 'jpg'), 'photo.jpg', 'Bénédiction', 'La foule devant le terrain');
        $this->assertSame(LibraryResult::ADDED, $first->status);
        $this->assertSame($this->user->id, $first->image->user_id);

        // Même image, plus petite et en WebP : doublon, l'original est conservé.
        $smaller = Library::add($this->photo(1, 600, 400, 'webp'), 'photo-reduite.webp');
        $this->assertSame(LibraryResult::DUPLICATE, $smaller->status);
        $this->assertTrue($smaller->image->is($first->image));

        // Même fichier exactement : doublon.
        $this->assertSame(LibraryResult::DUPLICATE, Library::add($this->photo(1, 1200, 800, 'jpg'), 'copie.jpg')->status);

        // Une autre photo : ajoutée.
        $this->assertSame(LibraryResult::ADDED, Library::add($this->photo(2, 1200, 800, 'jpg'), 'autre.jpg')->status);
        $this->assertSame(2, LibraryImage::count());
    }

    public function test_better_quality_replaces_the_source_and_keeps_title_and_description(): void
    {
        $small = Library::add($this->photo(3, 600, 400, 'jpg'), 'petite.jpg', 'Le clocher', 'Le clocher au coucher du soleil')->image;
        $oldSource = $small->source_path;

        $result = Library::add($this->photo(3, 2400, 1600, 'png'), 'grande.png');

        $this->assertSame(LibraryResult::REPLACED, $result->status);
        $image = $small->refresh();
        $this->assertSame('2400 × 1600', $image->dimensions());
        $this->assertSame('Le clocher', $image->title);
        $this->assertSame('Le clocher au coucher du soleil', $image->alt);
        Storage::disk('local')->assertMissing($oldSource);
        Storage::disk('local')->assertExists($image->source_path);
        $this->assertSame(1, LibraryImage::count());
    }

    public function test_word_document_images_are_extracted(): void
    {
        $results = Library::addUpload($this->docx([4, 5, 4]));

        $this->assertCount(3, $results);
        $this->assertSame('2 ajoutées, 1 déjà présente', LibraryResult::summary($results));
        $this->assertSame('Compte rendu – image 1', LibraryImage::oldest('id')->first()->title);
    }

    public function test_article_images_are_independent_copies(): void
    {
        $image = Library::add($this->photo(6, 2400, 1600, 'jpg'), 'photo.jpg', 'Chantier', 'Le chantier')->image;

        SiteServer::actingAs($this->user)->tool(PrepareArticleImage::class, ['id' => $image->id])
            ->assertOk()->assertSee('.webp')->assertSee('Le chantier');

        $copy = Storage::disk('public')->files('articles')[0];
        $this->assertSame(1920, getimagesizefromstring(Storage::disk('public')->get($copy))[0]);

        // Supprimer la source ne touche pas la copie de l'article.
        $image->delete();
        Storage::disk('public')->assertExists($copy);
        Storage::disk('local')->assertMissing($image->source_path);
    }

    public function test_search_and_describe_library_images(): void
    {
        Library::add($this->photo(7, 800, 600, 'jpg'), 'IMG_2041.jpg', 'Messe de rentrée');
        $other = Library::add($this->photo(8, 800, 600, 'jpg'), 'IMG_2042.jpg', 'Pique-nique')->image;

        SiteServer::actingAs($this->user)->tool(SearchLibrary::class, ['query' => 'rentrée'])
            ->assertOk()->assertSee('Messe de rentrée')->assertDontSee('Pique-nique');
        SiteServer::actingAs($this->user)->tool(SearchLibrary::class, [])->assertSee('Pique-nique');

        SiteServer::actingAs($this->user)->tool(UpdateLibraryImage::class, ['id' => $other->id, 'alt' => 'Des familles pique-niquent'])->assertOk();
        $this->assertSame('Des familles pique-niquent', $other->refresh()->alt);
    }

    public function test_import_from_url_goes_to_library_and_blocks_internal_addresses(): void
    {
        Http::fake(['93.184.215.14/*' => Http::response(file_get_contents($this->photo(9, 800, 600, 'png')), 200, ['Content-Type' => 'image/png'])]);

        SiteServer::actingAs($this->user)->tool(ImportImageFromUrl::class, ['url' => 'http://93.184.215.14/eglise.png', 'title' => 'Église'])
            ->assertOk()->assertSee('ajoutée');
        $this->assertSame('Église', LibraryImage::first()->title);

        SiteServer::actingAs($this->user)->tool(ImportImageFromUrl::class, ['url' => 'http://127.0.0.1/photo.png'])
            ->assertHasErrors(['Adresse non autorisée.']);
    }

    public function test_images_uploaded_through_the_api_join_the_library(): void
    {
        Passport::actingAs($this->user);

        $this->postJson('/api/media', ['file' => new UploadedFile($this->photo(10, 1000, 700, 'jpg'), 'chantier.jpg', null, null, true)])
            ->assertCreated();

        $this->assertSame(1, LibraryImage::count());
    }

    public function test_usages_are_indexed_for_articles_and_home(): void
    {
        $image = Library::add($this->photo(11, 1200, 800, 'jpg'), 'messe.jpg', 'Messe')->image;
        $cover = Library::copyForArticle($image);
        $inBody = Library::copyForArticle($image);

        $article = Article::factory()->create(['title' => 'Messe de rentrée', 'og_image' => $cover, 'og_image_alt' => 'x',
            'body' => "Texte\n\n![La messe](/storage/{$inBody})"]);
        $this->assertSame(['body', 'og_image'], $image->usages()->orderBy('field')->pluck('field')->all());

        $article->update(['og_image' => null]);
        $this->assertSame(['body'], $image->usages()->pluck('field')->all());

        Setting::put('home', ['hero' => ['image' => $cover]]);
        $this->assertSame(2, $image->usages()->count());

        // Recherche par titre de l'article qui utilise l'image.
        Livewire::test(ListLibraryImages::class)->set('search', 'rentrée')->assertSee('Messe')
            ->set('search', 'introuvable')->assertDontSee('Messe');
    }

    public function test_new_version_is_applied_page_by_page_at_a_new_address(): void
    {
        $image = Library::add($this->photo(12, 800, 600, 'jpg'), 'v1.jpg', 'Chantier')->image;
        $small = Library::copyForArticle($image, 'articles', 400);
        $big = Library::copyForArticle($image);
        $article = Article::factory()->create(['og_image' => $small, 'og_image_alt' => 'x', 'body' => "![x](/storage/{$small})

![y](/storage/{$big})"]);
        Setting::put('home', ['hero' => ['image' => $big]]);

        // La nouvelle version ne touche pas les pages.
        $this->assertSame(2, Library::replaceSource($image, $this->photo(13, 1600, 1200, 'jpg'), 'v2.jpg'));
        $this->assertTrue($image->usages()->with('copy.image')->get()->every(fn ($u) => $u->copy->isOutdated()));

        // Remplacement dans l'article (couverture + texte de la même copie), à sa taille actuelle (400 px).
        $usage = $image->usages()->where('article_id', $article->id)->where('field', 'og_image')->first();
        $new = Library::replaceInPage($usage);

        $article->refresh();
        $this->assertSame($new->path, $article->og_image);
        $this->assertStringContainsString($new->path, $article->body);
        $this->assertStringNotContainsString($small, $article->body);
        $this->assertSame(400, getimagesizefromstring(Storage::disk('public')->get($new->path))[0]);
        Storage::disk('public')->assertMissing($small); // plus utilisée nulle part

        // L'autre copie (accueil + corps de l'article) reste à l'ancienne version, puis est remplacée sur l'accueil seulement.
        $homeUsage = $image->usages()->where('page_type', 'home')->first();
        $this->assertTrue($homeUsage->copy->isOutdated());
        $home = Library::replaceInPage($homeUsage, 1200);
        $this->assertSame($home->path, Setting::get('home.hero.image'));
        $this->assertSame(1200, getimagesizefromstring(Storage::disk('public')->get($home->path))[0]);
        Storage::disk('public')->assertExists($big); // encore utilisée par le texte de l'article
        $this->assertSame('Chantier', $image->refresh()->title);
    }

    public function test_automatic_replacement_leaves_pages_on_the_old_version_until_refreshed(): void
    {
        $image = Library::add($this->photo(14, 600, 400, 'jpg'), 'petite.jpg')->image;
        $path = Library::copyForArticle($image);

        Library::add($this->photo(14, 2400, 1600, 'png'), 'grande.png'); // meilleure version détectée

        $copy = $image->copies()->first();
        $this->assertTrue($copy->isOutdated());
        $this->assertSame(600, getimagesizefromstring(Storage::disk('public')->get($path))[0]);

        $article = Article::factory()->create(['og_image' => $path, 'og_image_alt' => 'x']);
        $new = Library::replaceInPage($image->usages()->first(), Images::MAX_WIDTH);
        $this->assertFalse($new->isOutdated());
        $this->assertSame(1920, getimagesizefromstring(Storage::disk('public')->get($article->refresh()->og_image))[0]);
    }

    public function test_delete_is_refused_when_a_published_page_uses_the_image_and_cleans_drafts(): void
    {
        $image = Library::add($this->photo(15, 800, 600, 'jpg'), 'photo.jpg')->image;
        $path = Library::copyForArticle($image);
        $published = Article::factory()->create(['body' => "![x](/storage/{$path})"]);

        Livewire::test(EditLibraryImage::class, ['record' => $image->id])->assertActionDisabled('delete');
        try {
            Library::delete($image);
            $this->fail('La suppression aurait dû être refusée.');
        } catch (\RuntimeException) {
        }

        $published->delete();
        $draft = Article::factory()->draft()->create([
            'og_image' => $path, 'og_image_alt' => 'x', 'gallery' => [['path' => $path, 'alt' => 'x'], ['path' => 'autre.webp', 'alt' => 'y']],
            'body' => "Avant\n\n![x](/storage/{$path})\n\nAprès <img src=\"/storage/{$path}\" alt=\"x\">",
        ]);

        $this->assertSame([$draft->title], Library::delete($image));

        $draft->refresh();
        $this->assertNull($draft->og_image);
        $this->assertSame([['path' => 'autre.webp', 'alt' => 'y']], $draft->gallery);
        $this->assertStringNotContainsString($path, $draft->body);
        $this->assertStringContainsString('Après', $draft->body);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame(0, LibraryImage::count());
    }

    public function test_files_dropped_on_the_grid_are_added(): void
    {
        Livewire::test(ListLibraryImages::class)
            ->set('dropped', [UploadedFile::fake()->createWithContent('depose.jpg', file_get_contents($this->photo(16, 800, 600, 'jpg')))])
            ->assertNotified('Images : 1 ajoutée');

        $this->assertSame('Depose', LibraryImage::first()->title);
    }

    public function test_selected_images_can_be_downloaded_as_a_zip(): void
    {
        $a = Library::add($this->photo(18, 800, 600, 'jpg'), 'a.jpg', 'Procession')->image;
        $b = Library::add($this->photo(19, 800, 600, 'jpg'), 'b.jpg', 'Procession')->image;

        $response = $this->get(route('library.archive', ['ids' => "{$a->id},{$b->id}"]))->assertOk();

        $zip = new ZipArchive;
        $zip->open($response->baseResponse->getFile()->getPathname());
        $this->assertSame(['procession.jpg', 'procession-2.jpg'], [$zip->getNameIndex(0), $zip->getNameIndex(1)]);

        auth()->logout();
        $this->get(route('library.archive', ['ids' => $a->id]))->assertRedirect();
    }

    public function test_bulk_delete_keeps_images_used_by_published_pages(): void
    {
        $used = Library::add($this->photo(20, 800, 600, 'jpg'), 'utilisee.jpg', 'Utilisée')->image;
        Article::factory()->create(['body' => '![x](/storage/'.Library::copyForArticle($used).')']);
        $free = Library::add($this->photo(21, 800, 600, 'jpg'), 'libre.jpg', 'Libre')->image;

        Livewire::test(ListLibraryImages::class)
            ->set('selected', [$used->id, $free->id])
            ->callAction('deleteSelected')
            ->assertNotified('1 image(s) supprimée(s)')
            ->assertSet('selected', []);

        $this->assertModelExists($used);
        $this->assertModelMissing($free);
    }

    public function test_article_form_picks_images_from_the_library(): void
    {
        $cover = Library::add($this->photo(22, 1200, 630, 'jpg'), 'couverture.jpg', 'Le parvis', 'Le parvis au soleil')->image;
        $photo = Library::add($this->photo(23, 800, 600, 'jpg'), 'photo.jpg', 'La chorale')->image;
        $article = Article::factory()->draft()->create(['gallery' => []]);

        $this->getJson(route('library.search', ['q' => 'parvis']))->assertOk()
            ->assertJsonPath('items.0.id', $cover->id)->assertJsonCount(1, 'items');

        Livewire::test(EditArticle::class, ['record' => $article->id])
            ->callAction(TestAction::make('coverFromLibrary')->schemaComponent('og_image'), ['images' => [$cover->id]])
            ->assertHasNoActionErrors()
            ->callAction(TestAction::make('galleryFromLibrary')->schemaComponent('gallery'), ['images' => [$photo->id, $cover->id]])
            ->assertHasNoActionErrors()
            ->assertSchemaStateSet(['og_image_alt' => 'Le parvis au soleil'])
            ->call('save')
            ->assertHasNoFormErrors();

        $article->refresh();
        $this->assertStringStartsWith('articles/', $article->og_image);
        $this->assertSame(['La chorale', 'Le parvis au soleil'], array_column($article->gallery, 'alt'));
        $this->assertSame(['gallery', 'gallery', 'og_image'], LibraryUsage::where('article_id', $article->id)->orderBy('field')->pluck('field')->all());
    }

    public function test_unused_copies_are_cleaned_up_after_a_day(): void
    {
        $image = Library::add($this->photo(24, 800, 600, 'jpg'), 'a.jpg')->image;
        $orphan = Library::copyForArticle($image);
        $used = Library::copyForArticle($image);
        Article::factory()->create(['og_image' => $used, 'og_image_alt' => 'x']);

        $this->artisan('bibliotheque:nettoyer')->expectsOutputToContain('0 copie');
        $this->travel(2)->days();
        $this->artisan('bibliotheque:nettoyer')->expectsOutputToContain('1 copie');

        Storage::disk('public')->assertMissing($orphan);
        Storage::disk('public')->assertExists($used);
    }

    public function test_body_images_are_resized_to_their_display_width(): void
    {
        $image = Library::add($this->photo(25, 2400, 1600, 'jpg'), 'choeur.jpg', 'Le chœur')->image;
        $full = Library::copyForArticle($image, 'articles/contenu');
        $width = fn (string $body) => getimagesizefromstring(Storage::disk('public')->get(
            preg_match('#/storage/([^)]+)\)#', $body, $m) ? $m[1] : ''))[0];

        // Largeur 200 px → fichier de 400 px (écrans haute densité), nouvelle adresse.
        $article = Article::factory()->create(['body' => "Intro\n\n![Le chœur](/storage/{$full}){width=200}\n\nSuite"]);
        $this->assertStringNotContainsString($full, $article->body);
        $this->assertSame(400, $width($article->body));
        $this->assertStringContainsString('{width=200}', $article->body);
        $this->assertStringContainsString('<img width="200"', $article->bodyHtml());

        // On modifie la largeur dans le texte : le fichier suit.
        $article->update(['body' => str_replace('{width=200}', '{width=500}', $article->body)]);
        $this->assertSame(1000, $width($article->body));

        // Jamais plus grand que l'original (ni que 1920 px), et rien ne change si la taille est déjà bonne.
        $article->update(['body' => str_replace('{width=500}', '{width=1200}', $article->body)]);
        $this->assertSame(1920, $width($article->body));
        $before = $article->body;
        $article->update(['body' => $before."\n"]);
        $this->assertSame($before."\n", $article->body);

        // Une image qui ne vient pas de la bibliothèque est laissée telle quelle.
        $article->update(['body' => '![x](/storage/ailleurs/photo.webp){width=100}']);
        $this->assertSame('![x](/storage/ailleurs/photo.webp){width=100}', $article->body);
    }

    public function test_images_inserted_with_a_size_get_a_matching_file(): void
    {
        $image = Library::add($this->photo(26, 2000, 1500, 'jpg'), 'logo.jpg', 'Logo', 'Le logo')->image;
        $article = Article::factory()->draft()->create();

        Livewire::test(EditArticle::class, ['record' => $article->id])
            ->callAction(TestAction::make('markdownFromLibrary')->schemaComponent('body'), ['images' => [$image->id], 'size' => '250'])
            ->assertHasNoActionErrors();
        $this->assertSame(500, getimagesizefromstring(Storage::disk('public')->get($image->copies()->latest('id')->first()->path))[0]);

        SiteServer::actingAs($this->user)->tool(PrepareArticleImage::class, ['id' => $image->id, 'display_width' => 300])
            ->assertOk()->assertSee('{width=300}')->assertSee('![Le logo](/storage/articles/');
        $this->assertSame(600, getimagesizefromstring(Storage::disk('public')->get($image->copies()->latest('id')->first()->path))[0]);
    }

    public function test_crop_and_quick_rotation_apply_to_the_original_and_can_be_undone(): void
    {
        // 1600 × 1000, carré rouge en haut à gauche.
        $gd = imagecreatetruecolor(1600, 1000);
        imagefill($gd, 0, 0, imagecolorallocate($gd, 255, 255, 255));
        imagefilledrectangle($gd, 0, 0, 199, 199, imagecolorallocate($gd, 255, 0, 0));
        $file = tempnam(sys_get_temp_dir(), 'img').'.png';
        imagepng($gd, $file);
        $image = Library::add($file, 'coin.png', 'Coin rouge')->image;
        $copy = Library::copyForArticle($image);
        Article::factory()->create(['og_image' => $copy, 'og_image_alt' => 'x']);
        $red = function (string $bytes, int $x, int $y) {
            $rgb = imagecolorat(imagecreatefromstring($bytes), $x, $y);

            return ($rgb >> 16 & 0xFF) > 200 && ($rgb >> 8 & 0xFF) < 60; // rouge, pas blanc
        };
        $source = fn () => Storage::disk('local')->get($image->refresh()->source_path);

        // Quart de tour à droite (sens horaire, comme Cropper.js) : le coin rouge passe en haut à droite.
        Library::edit($image, ['rotate' => 90]);
        $this->assertSame('1000 × 1600', $image->refresh()->dimensions());
        $this->assertTrue($red($source(), 990, 10));
        $this->assertFalse($red($source(), 10, 10));
        $this->assertSame('image/png', $image->mime);
        $this->assertTrue($image->copies()->first()->isOutdated()); // les pages gardent l'ancienne version

        // Miroir + recadrage sur le coin rouge (désormais en haut à gauche).
        Library::edit($image, ['scaleX' => -1, 'x' => 0, 'y' => 0, 'width' => 300, 'height' => 300]);
        $this->assertSame('300 × 300', $image->refresh()->dimensions());
        $this->assertTrue($red($source(), 10, 10));
        $this->assertFalse($red($source(), 290, 290));

        // Annuler revient à l'état précédent (après la rotation), une seule fois.
        Library::undoEdit($image);
        $this->assertSame('1000 × 1600', $image->refresh()->dimensions());
        $this->assertNull($image->previous_source_path);
        $this->expectException(\InvalidArgumentException::class);
        Library::undoEdit($image);
    }

    public function test_jpeg_quality_is_estimated(): void
    {
        $img = imagecreatefromjpeg($this->photo(17, 800, 600, 'jpg'));
        ob_start();
        imagejpeg($img, null, 72);
        $this->assertSame(72, ImageInfo::jpegQuality(ob_get_clean()));
    }

    /** Image au contenu propre à $seed (bandes et formes), pour que les empreintes diffèrent d'une photo à l'autre. */
    private function photo(int $seed, int $width, int $height, string $format): string
    {
        mt_srand($seed);
        $img = imagecreatetruecolor(600, 400);
        for ($i = 0; $i < 40; $i++) {
            $color = imagecolorallocate($img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagefilledellipse($img, mt_rand(0, 600), mt_rand(0, 400), mt_rand(40, 260), mt_rand(40, 260), $color);
        }
        $scaled = imagescale($img, $width, $height);
        $path = tempnam(sys_get_temp_dir(), 'img').'.'.$format;
        match ($format) {
            'jpg' => imagejpeg($scaled, $path, 85),
            'png' => imagepng($scaled, $path),
            'webp' => imagewebp($scaled, $path, 70),
        };

        return $path;
    }

    /** @param  array<int, int>  $seeds */
    private function docx(array $seeds): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<w:document/>');
        foreach ($seeds as $n => $seed) {
            $zip->addFile($this->photo($seed, 600, 400, 'png'), 'word/media/image'.($n + 1).'.png');
        }
        $zip->close();

        return new UploadedFile($path, 'Compte rendu.docx', null, null, true);
    }
}
