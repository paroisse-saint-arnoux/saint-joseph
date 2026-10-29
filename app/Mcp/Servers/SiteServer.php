<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateArticle;
use App\Mcp\Tools\GetPage;
use App\Mcp\Tools\ImportImageFromUrl;
use App\Mcp\Tools\ListArticles;
use App\Mcp\Tools\PrepareArticleImage;
use App\Mcp\Tools\SearchLibrary;
use App\Mcp\Tools\UpdateArticle;
use App\Mcp\Tools\UpdateHomeSection;
use App\Mcp\Tools\UpdateLegalPage;
use App\Mcp\Tools\UpdateLibraryImage;
use App\Mcp\Tools\ViewLibraryImage;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * Connecteur Claude du site (https://saint-joseph.saint-arnoux.fr/mcp), authentifié par OAuth (Passport).
 * Les outils agissent au nom de l'utilisateur connecté. Pas d'outil de suppression.
 */
#[Name('Site Saint Joseph')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
Ce connecteur permet de rédiger et modifier le site de l'église Saint Joseph (Paroisse Saint-Arnoux, Le Rouret) :
articles de la rubrique « Le projet », page d'accueil, mentions légales. Les utilisateurs sont des bénévoles
de la paroisse, souvent peu familiers de l'informatique : expliquer simplement, en français, sans jargon technique.

## Créer un article à partir d'un document
1. Lire le document. Choisir UNE catégorie : agenda (événements), architecture, budget, construction (travaux),
   projet (vie du projet, dons), presentation (présentation générale), equipe (les personnes).
2. Rédiger : titre court et clair ; chapô (excerpt) d'une ou deux phrases ; corps en Markdown structuré par des
   intertitres (##), paragraphes courts, listes si utile. Rester fidèle au document : ne rien inventer
   (ni chiffres, ni dates, ni noms). Ne pas répéter le titre ni le chapô dans le corps.
3. Images : elles viennent toujours de la **bibliothèque d'images** du site (search-library).
   - Si l'utilisateur désigne des images (titre, nom de fichier, « les photos que je viens d'ajouter »), les
     retrouver avec search-library.
   - Si le document contient des images, ou si l'utilisateur veut des photos qu'il n'a pas encore ajoutées :
     ne pas inventer. Lui demander de les ajouter d'abord dans la bibliothèque (Admin > Bibliothèque d'images,
     adresse donnée par search-library) en y glissant ses photos ou directement son fichier Word (.docx),
     puis de revenir le dire ; ensuite search-library sans « query » montre les dernières images ajoutées.
     Les doublons sont détectés automatiquement : une image déjà présente n'est pas ajoutée deux fois.
   - Pour chaque image retenue, appeler prepare-article-image (copie propre à l'article) et utiliser le path
     (og_image, gallery) ou le markdown renvoyé (corps, sur sa propre ligne). Pour une image du corps affichée
     en petit (portrait, logo, illustration secondaire), passer display_width (250, 400 ou 600).
   - Choisir une image de couverture (og_image + og_image_alt). Toujours une description (alt) précise ;
     si l'image n'en a pas dans la bibliothèque, la compléter avec update-library-image.
   - Pour une image publiée sur le web : import-image-from-url (l'ajoute à la bibliothèque).
4. Appeler create-article (brouillon par défaut). Ne publier (status = published) que si l'utilisateur le demande.
5. Toujours terminer en donnant le lien d'édition dans l'admin (admin_url) et le lien d'aperçu (preview_url).

## Modifier une page
L'utilisateur donne une adresse du site : appeler get-page, faire la modification demandée en conservant tout le
reste à l'identique, puis update-article, update-home-section ou update-legal-page. Résumer ce qui a changé et
donner le lien. Les modifications d'un article publié, de l'accueil ou des pages légales sont en ligne immédiatement.

## Mise en forme
- Markdown (GitHub) : intertitres ##, gras, listes, tableaux, liens. Un lien YouTube seul sur sa ligne devient une vidéo.
  Taille d'une image du corps : {width=400} juste après l'image ; changer ce nombre suffit, le fichier est
  redimensionné automatiquement à l'enregistrement.
- Format html seulement pour une mise en page riche demandée explicitement. Classes disponibles : grille Bootstrap
  (row, col-md-6…), ratio ; .team-grid > .member (photo, nom, .member-role, .member-bio) pour une équipe.
- Ton : chaleureux, sobre, accessible à tous. Écrire « Saint Joseph ». Ne jamais écrire « SJO2 » (code interne).
- Ne pas mentionner d'auteur ni de temps de lecture.
MARKDOWN)]
class SiteServer extends Server
{
    protected array $tools = [
        ListArticles::class,
        GetPage::class,
        CreateArticle::class,
        UpdateArticle::class,
        SearchLibrary::class,
        ViewLibraryImage::class,
        PrepareArticleImage::class,
        UpdateLibraryImage::class,
        ImportImageFromUrl::class,
        UpdateHomeSection::class,
        UpdateLegalPage::class,
    ];
}
