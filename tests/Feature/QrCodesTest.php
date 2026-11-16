<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\CroixVisitor;
use App\Models\ShortLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodesTest extends TestCase
{
    use RefreshDatabase;

    private function create(array $data)
    {
        return $this->postJson('/qrcodes', $data);
    }

    public function test_the_page_is_public_but_not_indexed_and_lists_the_public_pages(): void
    {
        $published = Article::factory()->create(['title' => 'Article en ligne']);
        Article::factory()->draft()->create(['title' => 'Brouillon secret']);

        $this->get('/qrcodes')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('value="/don"', false)
            ->assertSee('value="/croix"', false)
            ->assertSee('value="'.parse_url($published->url(), PHP_URL_PATH).'"', false)
            ->assertSee('Article en ligne')
            ->assertDontSee('Brouillon secret')
            ->assertSee('Affiche (QR code)')
            ->assertDontSee('SJO2');

        $this->get('/croix/liens')->assertRedirect('/qrcodes')->assertStatus(301);
        $this->get('/sitemap.xml')->assertDontSee('/qrcodes');
    }

    public function test_it_creates_a_short_link_and_its_qr_code(): void
    {
        $response = $this->create(['target' => '/croix', 'source' => 'affiche', 'content' => 'Église du Rouret'])->assertOk();

        $link = ShortLink::sole();
        $this->assertMatchesRegularExpression('/^[a-z2-9]{5}$/', $link->code);
        $this->assertMatchesRegularExpression('/\d/', $link->code);
        $this->assertSame(url('/croix').'?utm_source=affiche&utm_medium=print&utm_campaign=croix&utm_content=eglise-du-rouret', $link->url);
        $response->assertJson(['short_url' => 'https://sj.saint-arnoux.fr/'.$link->code, 'url' => $link->url])
            ->assertJsonPath('png', fn ($png) => str_starts_with($png, 'data:image/png;base64,'))
            ->assertJsonPath('svg', fn ($svg) => str_contains($svg, '<svg'));

        // Fond transparent : PNG avec le blanc déclaré transparent, SVG sans modules clairs.
        $png = base64_decode(substr($response->json('png'), strlen('data:image/png;base64,')));
        $image = imagecreatefromstring($png);
        $this->assertSame(imagecolortransparent($image), imagecolorat($image, 0, 0));
        $this->assertStringNotContainsString('light', $response->json('svg'));
        // Bleu de la charte (#00204E) : premier module du motif de repérage, en haut à gauche après la marge (2 × 24 px).
        $this->assertSame(['red' => 0, 'green' => 32, 'blue' => 78], array_slice(imagecolorsforindex($image, imagecolorat($image, 60, 60)), 0, 3));
        $this->assertStringContainsString('#00204E', $response->json('svg'));

        // Mêmes choix : même lien court.
        $this->create(['target' => '/croix', 'source' => 'affiche', 'content' => 'eglise du rouret'])->assertJson(['short_url' => $link->shortUrl()]);
        $this->assertSame(1, ShortLink::count());
    }

    public function test_campaign_and_anchor(): void
    {
        $this->create(['target' => '/#collecte', 'source' => 'facebook'])->assertOk()
            ->assertJson(['url' => url('/').'?utm_source=facebook&utm_medium=social&utm_campaign=saint-joseph#collecte']);
        $this->create(['target' => '/huile', 'source' => 'etiquette'])->assertOk()
            ->assertJson(['url' => url('/huile').'?utm_source=etiquette&utm_medium=print&utm_campaign=huile']);
    }

    public function test_the_campaign_can_be_chosen(): void
    {
        $this->get('/qrcodes')->assertSee('data-campaign="croix"', false)->assertSee('<option value="saint-joseph">', false);

        $this->create(['target' => '/don', 'source' => 'flyer', 'campaign' => 'Noël 2026'])->assertOk()
            ->assertJson(['url' => url('/don').'?utm_source=flyer&utm_medium=print&utm_campaign=noel-2026']);
        $this->create(['target' => '/don', 'source' => 'flyer'])->assertOk() // sans campagne : celle de la page
            ->assertJson(['url' => url('/don').'?utm_source=flyer&utm_medium=print&utm_campaign=don']);

        $this->assertSame(2, ShortLink::count());
        $this->get('/qrcodes')->assertSee('<option value="noel-2026">', false); // proposée ensuite
    }

    public function test_only_public_pages_and_listed_sources_are_accepted(): void
    {
        $draft = Article::factory()->draft()->create();

        $this->create(['target' => 'https://example.com/', 'source' => 'affiche'])->assertJsonValidationErrors('target');
        $this->create(['target' => '/admin', 'source' => 'affiche'])->assertJsonValidationErrors('target');
        $this->create(['target' => parse_url($draft->url(), PHP_URL_PATH), 'source' => 'affiche'])->assertJsonValidationErrors('target');
        $this->create(['target' => '/croix', 'source' => 'inconnu'])->assertJsonValidationErrors('source');
        $this->assertSame(0, ShortLink::count());
    }

    public function test_the_short_link_redirects_without_counting_anything(): void
    {
        $this->create(['target' => '/croix', 'source' => 'flyer']);
        $link = ShortLink::sole();

        $response = $this->get('https://sj.saint-arnoux.fr/'.strtoupper($link->code));
        $response->assertRedirect($link->url)->assertStatus(302);
        $this->assertEmpty($response->headers->getCookies(), 'ni session ni cookie sur le domaine court');
        $this->assertSame(0, CroixVisitor::count());

        // Autre adresse du domaine court : renvoyée vers le site.
        $site = rtrim(config('app.url'), '/');
        $this->get('https://sj.saint-arnoux.fr/')->assertRedirect($site.'/');
        $this->get('https://sj.saint-arnoux.fr/le-projet')->assertRedirect($site.'/le-projet');
        $this->get('https://sj.saint-arnoux.fr/zz2zz')->assertRedirect($site.'/zz2zz');

        // Si le domaine court redirige vers le site en gardant le chemin.
        $this->get($site.'/'.$link->code)->assertRedirect($link->url);
        $this->get($site.'/zz2zz')->assertNotFound();
    }
}
