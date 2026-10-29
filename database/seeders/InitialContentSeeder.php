<?php

namespace Database\Seeders;

use App\Enums\ArticleFormat;
use App\Enums\ArticleStatus;
use App\Enums\Category;
use App\Enums\Role;
use App\Models\Article;
use App\Models\Setting;
use App\Models\User;
use App\Support\Images;
use Illuminate\Database\Seeder;

/**
 * Contenu de départ, repris de l'ancien one-pager (dossier legacy/).
 * Idempotent : ne crée que ce qui n'existe pas encore.
 */
class InitialContentSeeder extends Seeder
{
    private string $assets = __DIR__.'/assets';

    public function run(): void
    {
        $this->seedAdmin();

        if (! Setting::find('home')) {
            Setting::put('home', $this->home());
        }
        if (! Setting::find('site')) {
            Setting::put('site', $this->site());
        }
        if (! Setting::find('legal')) {
            Setting::put('legal', $this->legal());
        }

        if (! Article::where('slug', 'l-equipe-du-projet')->exists()) {
            $this->seedTeamArticle();
        }
        if (! Article::where('slug', 'le-budget-du-projet')->exists()) {
            $this->seedBudgetArticle();
        }
        if (! Article::where('slug', 'vos-questions-sur-le-don')->exists()) {
            $this->seedFaqArticle();
        }
    }

    private function seedAdmin(): void
    {
        $email = config('services.admin_email');

        if (blank($email) || User::exists()) {
            return;
        }

        User::create(['name' => 'Administrateur', 'email' => mb_strtolower($email), 'role' => Role::Admin, 'is_active' => true]);
    }

    private function home(): array
    {
        return [
            'hero' => [
                'title' => "Bâtir un lieu de *Foi*,\nde *Vie* et d'*Espérance*.",
                'facts' => [
                    '**≈ 1 000 m²** sur 1 ha de terrain',
                    'Un budget global de **6 M€**',
                    '**400** personnes accueillies',
                    'Livraison prévue en **2030**',
                ],
                'image' => Images::store($this->assets.'/hero-eglise-nuit.jpeg', 'accueil'),
            ],
            'decouvrir' => [
                'eyebrow' => 'Découvrir le projet',
                'title' => 'Une église et un centre paroissial pour la Paroisse Saint-Arnoux.',
                'text' => "La Paroisse Saint-Arnoux rassemble neuf communes autour du Rouret. Portée par une communauté vivante et en pleine croissance, elle a besoin d'un lieu à sa mesure pour célébrer, accueillir, former et rayonner.\n\n"
                    ."Sur un terrain d'un hectare acquis au Rouret, le projet Saint Joseph réunira **une église de plus de 300 places**, **un centre paroissial** ouvert à tous — grande salle, salles de catéchisme et d'aumônerie, café, espaces de rencontre — et **un presbytère**. Un lieu pensé pour durer, au cœur de la vie du village.\n\n"
                    .'Ce projet est celui de toute la communauté : chacun peut y prendre part, par la prière, par son engagement bénévole ou par son don.',
                'image' => null,
                'image_alt' => null,
            ],
            'projet' => [
                'eyebrow' => 'Le projet',
                'title' => 'Un projet immobilier structuré et conçu pour la mission.',
                'intro' => 'La Paroisse Saint-Arnoux, créée par le regroupement de ses 9 communes, bâtit un centre paroissial et son église pour accueillir la forte croissance missionnaire.',
                'stats' => [
                    ['value' => '8736', 'unit' => 'm²', 'label' => 'Parcelle acquise'],
                    ['value' => '6', 'unit' => 'M€', 'label' => 'Budget'],
                    ['value' => '400', 'unit' => 'pers.', 'label' => "Capacité d'accueil"],
                    ['value' => '70', 'unit' => '%', 'label' => 'Espaces paysagers'],
                ],
                'buildings' => [
                    ['title' => 'Le Centre Paroissial', 'badge' => '+ de 100 personnes', 'items' => "Accueil & espaces de rencontre\nUne grande salle polyvalente pour accueillir les grands évènements\nSalles de catéchisme et d'aumônerie\nUn café, une cuisine, des espaces de rangement\nEspaces missionnaires\nVie quotidienne de la paroisse"],
                    ['title' => "L'Église", 'badge' => '+ 300 personnes', 'items' => "Nef principale\nClocher (≈ 22 m)\nParvis & espaces extérieurs\nLieu liturgique durable"],
                    ['title' => 'Le Presbytère', 'badge' => '3 logements', 'items' => "3 logements (T3) de prêtres\nLieu de vie de la communauté sacerdotale"],
                ],
                'images' => [
                    [
                        'path' => Images::store($this->assets.'/l-ensemble-paroissial-dans-son-site.jpeg', 'accueil'),
                        'alt' => "L'ensemble paroissial dans son site",
                        'caption' => "L'ensemble paroissial dans son site (vue d'intention, en attendant les esquisses de l'architecte)",
                    ],
                ],
            ],
            'avancement' => [
                'eyebrow' => "L'avancement",
                'title' => 'Une dynamique déjà lancée.',
                'text' => 'Le terrain est acquis, le projet validé par le Diocèse. Les études sont faites et la sélection des architectes est en cours.',
                'steps' => [
                    ['date' => '2025', 'title' => 'Acquisition du terrain', 'description' => 'Achat et paiement intégral du terrain au Rouret.', 'status' => 'done'],
                    ['date' => '2026', 'title' => 'Validation du Diocèse', 'description' => 'Faisabilité et plan de financement approuvés : lors du CID le 7 mai, puis du CDAE le 19 mai.', 'status' => 'done'],
                    ['date' => '2026', 'title' => 'Études préalables', 'description' => 'Études techniques et réglementaires réalisées.', 'status' => 'done'],
                    ['date' => '2026', 'title' => 'Sélection des architectes', 'description' => "Consultation en cours pour retenir l'équipe de maîtrise d'œuvre.", 'status' => 'now'],
                    ['date' => '2026–2027', 'title' => 'Campagne de dons', 'description' => 'Mobilisation des donateurs et mécènes pour financer la construction.', 'status' => 'now'],
                    ['date' => '2027–2030', 'title' => 'Autorisations, travaux & livraison', 'description' => 'Permis de construire, consultation des entreprises, construction puis ouverture — Centre paroissial puis Église.', 'status' => 'todo'],
                ],
            ],
            'collecte' => [
                'eyebrow' => 'Le Clocher des Bâtisseurs',
                'title' => 'Faites monter le clocher.',
                'text' => "Chaque don fait grandir le clocher de Saint-Joseph. Chaque don de 10 € pose une pierre : le clocher s'élève au rythme de la générosité.",
                'raised' => 1400000,
                'goal' => 6000000,
                'goal_label' => 'les cloches en 2030',
                'quote' => '« Chaque pierre fait monter le clocher. Ensemble, bâtissons-le pour que les cloches sonnent en 2030. »',
            ],
        ];
    }

    private function site(): array
    {
        return [
            'description' => "La Paroisse Saint-Arnoux bâtit l'église Saint Joseph et son centre paroissial au Rouret (Alpes-Maritimes). Découvrez le projet et soutenez la construction par un don.",
            'about' => 'Centre paroissial et église Saint Joseph, portés par la Paroisse Saint-Arnoux, Association Diocésaine de Nice, au Rouret (06).',
            'address' => '30 Allée du Pré des Roures · 06650 Le Rouret',
            'don_url' => 'https://don-nice.catholique.fr/centre-paroissial-st-arnoux-rouret/',
            'facebook_url' => 'https://www.facebook.com/saint.joseph.le.rouret',
            'linkedin_url' => 'https://www.linkedin.com/company/saint-joseph-le-b%C3%A2tisseur/',
            'useful_links' => [
                ['label' => 'Paroisse Saint-Arnoux', 'url' => 'https://saint-arnoux.fr/'],
                ['label' => 'Diocèse de Nice', 'url' => 'https://nice.catholique.fr/'],
                ['label' => 'Plateforme de don du diocèse', 'url' => 'https://don-nice.catholique.fr/centre-paroissial-st-arnoux-rouret/'],
                ['label' => 'Commune du Rouret', 'url' => 'https://lerouret.fr/'],
            ],
            'newsletter_title' => 'Suivez la construction.',
            'newsletter_text' => 'Recevez des nouvelles du projet : avancement du chantier, temps forts et rendez-vous de la communauté.',
            'don_title' => 'Chaque pierre compte.',
            'don_text' => 'Votre don est déductible à 66 % de votre impôt sur le revenu : un don de 100 € ne vous coûte que 34 €.',
        ];
    }

    private function legal(): array
    {
        return [
            'mentions' => <<<'MD'
## Éditeur du site

Paroisse Saint-Arnoux — Association Diocésaine de Nice
30 Allée du Pré des Roures, 06650 Le Rouret

Email : [information.saint-joseph@saint-arnoux.fr](mailto:information.saint-joseph@saint-arnoux.fr)

Directeur de la publication : *à compléter*

## Hébergement

o2switch — SARL au capital de 100 000 €
222-224 Boulevard Gustave Flaubert, 63000 Clermont-Ferrand, France
[www.o2switch.fr](https://www.o2switch.fr)

## Propriété intellectuelle

Les textes, photographies et visuels de ce site sont la propriété de la Paroisse Saint-Arnoux ou de leurs auteurs. Toute reproduction sans autorisation est interdite.

## Données personnelles

Voir notre [politique de confidentialité](/confidentialite).
MD,
            'privacy' => <<<'MD'
La Paroisse Saint-Arnoux (Association Diocésaine de Nice) est responsable des données collectées sur ce site. Elles ne sont jamais vendues ni cédées à des tiers.

## Formulaire de contact

Votre nom, votre adresse email et votre message nous sont transmis par email afin de vous répondre. Ils ne sont pas conservés sur le site.

## Lettre d'information

Votre prénom, votre nom et votre adresse email sont enregistrés chez notre prestataire d'emailing **Brevo** (Sendinblue SAS, France) pour vous envoyer des nouvelles du projet. Chaque lettre contient un lien de désinscription.

## Mesure d'audience

Nous utilisons **Matomo**, hébergé par la paroisse, configuré sans cookie et avec des adresses IP anonymisées. Aucune donnée n'est partagée avec des tiers.

## Vos droits

Conformément au RGPD, vous disposez d'un droit d'accès, de rectification, d'opposition et de suppression de vos données. Écrivez-nous à [information.saint-joseph@saint-arnoux.fr](mailto:information.saint-joseph@saint-arnoux.fr). Vous pouvez aussi adresser une réclamation à la [CNIL](https://www.cnil.fr).
MD,
        ];
    }

    private function seedTeamArticle(): void
    {
        $members = json_decode(file_get_contents($this->assets.'/equipe.json'), true);
        $photo = fn (string $file) => '/storage/'.Images::store($this->assets."/equipe/{$file}.jpeg", 'equipe', 600);

        $cards = collect($members)->map(fn (array $m) => sprintf(
            '<button type="button" class="member"><img src="%s" alt="%s" loading="lazy"><span class="nm">%s</span><span class="more">Voir la fiche ›</span><span class="member-role">%s</span><span class="member-bio">%s</span></button>',
            $photo($m['file']), e($m['name']), e($m['name']), e($m['role']), e($m['bio'])
        ))->implode("\n  ");

        $priest = $photo('pere-paul-bao-dinh-ly');

        $body = <<<HTML
<div class="moa">
  <img class="photo" src="{$priest}" alt="Père Paul Bao Dinh LY">
  <div>
    <span class="role">Curé de la Paroisse Saint-Arnoux</span>
    <h3>Père Paul Bao Dinh LY</h3>
    <blockquote>« Nous ne bâtissons pas seulement des murs. Nous bâtissons un lieu vivant. »</blockquote>
    <p class="moa-bio">Pilote principal du projet et maître d'ouvrage en tant que représentant du Diocèse de Nice, le Père Bao Dinh LY est le garant de la vision pastorale et de la relation avec l'évêché. Porté par une vision stratégique large — à l'échelle de la paroisse comme du diocèse — il défend les décisions du projet et conduit la communauté avec une volonté résolue de le mener à bien.</p>
  </div>
</div>

<h2>Des compétences au service de la mission</h2>
<div class="comp-grid">
  <div class="comp">Pilotage professionnel</div>
  <div class="comp">Architecture</div>
  <div class="comp">Finances</div>
  <div class="comp">Construction</div>
  <div class="comp">Bureaux d'études</div>
  <div class="comp">Communication</div>
  <div class="comp">Levée de fonds</div>
  <div class="comp">Gestion de projet</div>
</div>

<div class="benev">
  <span class="big">100+</span>
  <p><b>Plus de 100 bénévoles</b> sont engagés autour du projet, avec une exigence partagée : maîtrise des coûts et des risques, sécurité, pérennité constructive et infrastructures.</p>
</div>

<h2>L'équipe projet</h2>
<p>Cliquez sur une personne pour découvrir son parcours.</p>
<div class="team-grid">
  {$cards}
</div>

<p class="team-verse">« Là où deux ou trois sont réunis en mon nom, je suis au milieu d'eux » — Mt 18, 20</p>
HTML;

        Article::create([
            'title' => "L'équipe du projet",
            'slug' => 'l-equipe-du-projet',
            'category' => Category::Equipe,
            'excerpt' => "Le projet est porté par la Paroisse Saint-Arnoux et l'Association Diocésaine de Nice, accompagnées d'une équipe d'experts structurée et bénévole.",
            'format' => ArticleFormat::Html,
            'body' => $body,
            'og_image' => Images::store($this->assets.'/hero-eglise-nuit.jpeg', 'articles'),
            'og_image_alt' => "L'église Saint Joseph du Rouret, vue de nuit",
            'status' => ArticleStatus::Published,
            'published_at' => now(),
        ]);
    }

    private function seedBudgetArticle(): void
    {
        Article::create([
            'title' => 'Le budget du projet',
            'slug' => 'le-budget-du-projet',
            'category' => Category::Budget,
            'excerpt' => "Un coût global maîtrisé de 6 M€ TTC, dont 1,4 M€ déjà réunis (terrain inclus). Voici la répartition de l'enveloppe et le calendrier de réalisation.",
            'format' => ArticleFormat::Markdown,
            'body' => <<<'MD'
## Les chiffres clés

| | |
|---|---|
| **Coût global** | 6 M€ TTC |
| **Déjà réuni** (terrain inclus) | 1,4 M€ |
| **Reste à financer** | 4,6 M€ TTC |

## Répartition de l'enveloppe travaux

| Poste | Montant | Part |
|---|---|---|
| Construction & travaux | 3 M€ | 60 % |
| VRD · accès · stationnement | 750 K€ | 15 % |
| Honoraires & assurances | 700 K€ | 14 % |
| Aménagements | 400 K€ | 8 % |
| Études & conception | 150 K€ | 3 % |

*Répartition indicative de l'enveloppe travaux (hors part déjà couverte par l'acquisition du terrain).*

## Le calendrier

- **2025** — Acquisition du terrain
- **2025-2026** — Études & concours d'architecture
- **2026** — Autorisations
- **2027-2029** — Travaux
- **2029-2030** — Aménagements
- **2030** — Ouverture

> « Chaque don, quel que soit son montant, contribue à bâtir un lieu de foi, de communion et d'espérance. »
MD,
            'status' => ArticleStatus::Published,
            'published_at' => now()->subMinute(),
        ]);
    }

    private function seedFaqArticle(): void
    {
        Article::create([
            'title' => 'Vos questions sur le don',
            'slug' => 'vos-questions-sur-le-don',
            'category' => Category::Projet,
            'excerpt' => 'Les réponses aux questions les plus fréquentes sur le soutien au projet : déduction fiscale, dons par chèque, autres façons d\'aider.',
            'format' => ArticleFormat::Markdown,
            'body' => <<<'MD'
## Pourquoi la paroisse fait-elle appel à la générosité de ses fidèles ?

L'aménagement et la construction des bâtiments paroissiaux représentent un coût important. Pour continuer à accueillir, célébrer, accompagner et annoncer l'Évangile dans de bonnes conditions, la paroisse doit engager des investissements durables. La générosité de chacun rend ce projet possible.

## Comment faire un don ?

- **En ligne**, de façon sécurisée, sur la [plateforme de don du diocèse](https://don-nice.catholique.fr/centre-paroissial-st-arnoux-rouret/).
- **Par chèque** : *informations à compléter (ordre, adresse d'envoi, mention à indiquer au dos).*

## Puis-je bénéficier d'une déduction fiscale ?

Oui. Les dons consentis au profit de l'Église ouvrent droit, pour les particuliers imposables, à une réduction d'impôt de **66 %** du montant du don, dans la limite de 20 % du revenu imposable. Ainsi, un don de 200 € ne vous coûte réellement que 68 €. Pour les entreprises, la réduction est de **60 %**. Un reçu fiscal vous est adressé dans tous les cas.

## Mon don peut-il vraiment faire la différence ?

Oui. Chaque don compte, petit ou grand : chacun pose une pierre de plus à l'édifice. C'est par l'engagement de tous que ce projet verra le jour.

## Comment aider si je ne peux pas donner financièrement ?

En relayant notre appel auprès de votre entourage, vous permettez à notre message de toucher davantage de personnes et vous contribuez directement au succès de la campagne. Le partage est une aide précieuse — et vous pouvez aussi rejoindre nos équipes de bénévoles en nous [écrivant](/contact).
MD,
            'status' => ArticleStatus::Draft,
        ]);
    }
}
