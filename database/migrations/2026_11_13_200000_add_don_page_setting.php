<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

// Textes de la page /don (Admin > Contenu > Page de don), repris de la page de la campagne RGIVE du diocèse
// (don-nice.catholique.fr/centre-paroissial-st-arnoux-rouret/), avec l'avantage fiscal des entreprises.
return new class extends Migration
{
    public function up(): void
    {
        if (Setting::find('don')) {
            return;
        }

        Setting::put('don', [
            'hero' => [
                'eyebrow' => 'Faire un don',
                'title' => 'Bâtir un lieu de foi, de rencontre et d\'espérance.',
                'lead' => 'Un lieu beau, ouvert et fraternel, pensé pour accueillir, célébrer et accompagner la vie de la communauté pour les décennies à venir.',
            ],
            'gauge_text' => 'Votre don est déductible à 66 % de votre impôt sur le revenu (60 % pour les entreprises) : un don de 100 € ne vous coûte que 34 €.',
            'why' => [
                'eyebrow' => 'Pourquoi ce projet',
                'title' => 'Des infrastructures qui ne répondent plus aux besoins',
                'text' => "Aujourd'hui, les bâtiments de la paroisse ne suffisent plus à la vie pastorale et communautaire. Les églises sont trop petites lors des grandes célébrations — Noël, communions, rassemblements ; il n'existe pas de lieu adapté à la formation, et le catéchisme comme l'aumônerie se tiennent dans des chalets anciens, peu confortables et inadaptés.\n\nL'absence de stationnement crée une gêne pour les riverains et empêche les parents de rester, d'accompagner les jeunes et de participer pleinement à la vie paroissiale. Ces contraintes freinent l'accueil et l'élan missionnaire.",
            ],
            'projet' => [
                'eyebrow' => 'Le projet en deux phases',
                'title' => 'Un programme au service de la communauté',
                'text' => "804 m² au total, organisés en deux phases : un centre paroissial moderne, puis une nouvelle église. Deux lieux fonctionnels, accessibles et ouverts à tous.\n\n[Découvrir le projet →](/le-projet)",
            ],
            'testimony' => [
                'quote' => 'Ce nouveau centre paroissial est une vraie chance pour notre communauté. Il offrira des lieux adaptés pour accueillir ceux qui souhaitent vivre, partager et approfondir leur foi. En soutenant ce projet, je contribue concrètement à l\'avenir de notre paroisse.',
                'author' => 'Claire, paroissienne et maman',
            ],
            'fiscal' => [
                'eyebrow' => 'Votre don, soutenu par la fiscalité',
                'title' => 'Déduction fiscale',
                'individuals' => "Les dons au profit de l'Église ouvrent droit, pour les particuliers imposables, à une **réduction d'impôt sur le revenu de 66 %** de leur montant, dans la limite de 20 % du revenu imposable.\n\nUn don de 100 € revient ainsi à **34 €**.",
                'companies' => "Les dons des entreprises ouvrent droit à une **réduction d'impôt de 60 %** de leur montant (40 % pour la fraction au-delà de 2 M€), dans la limite de 20 000 € ou de 5 ‰ du chiffre d'affaires.\n\nUn don de 1 000 € revient ainsi à **400 €**.",
                'note' => 'Un reçu fiscal vous est adressé par l\'Association diocésaine de Nice. Pour plus d\'informations, consultez la réglementation fiscale en vigueur ou votre conseiller fiscal.',
            ],
            'faq' => [
                ['question' => 'Pourquoi la paroisse fait-elle appel à la générosité de ses fidèles ?', 'answer' => "L'aménagement et la construction des bâtiments paroissiaux représentent un coût important. Pour continuer à accueillir, célébrer, accompagner et annoncer l'Évangile dans de bonnes conditions, la paroisse doit engager des investissements durables. La générosité de chacun rend ces projets possibles."],
                ['question' => 'Comment aider si je ne peux pas donner financièrement ?', 'answer' => "En relayant notre appel auprès de votre entourage, vous permettez à notre message de toucher davantage de personnes et vous contribuez directement au succès de la campagne. Le partage est une aide précieuse."],
                ['question' => 'Mon don peut-il vraiment faire la différence ?', 'answer' => "Oui. Chaque don compte, petit ou grand — chacun pose une pierre de plus à l'édifice. C'est par l'engagement de tous que ce projet pourra voir le jour."],
                ['question' => 'Puis-je bénéficier d\'une déduction fiscale ?', 'answer' => "Oui. Les dons consentis au profit de l'Église ouvrent droit, pour les particuliers imposables, à une réduction d'impôt de 66 %, dans la limite de 20 % du revenu imposable : un don de 100 € revient en réalité à 34 €. Pour les entreprises, la réduction d'impôt est de 60 % du montant du don, dans la limite de 20 000 € ou de 5 ‰ du chiffre d'affaires."],
            ],
        ]);
    }

    public function down(): void
    {
        Setting::where('key', 'don')->delete();
    }
};
