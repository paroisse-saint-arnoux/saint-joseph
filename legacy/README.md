# Site SJO2 — Église Saint Joseph (Le Rouret)

Site du projet de centre paroissial et d'église Saint Joseph, porté par la
Paroisse Saint-Arnoux — Association Diocésaine de Nice (ADN), au Rouret (06).
Destination : https://saint-joseph.saint-arnoux.fr/

## Structure
```
index.html            → la page (HTML seul)
css/style.css         → toute la mise en forme
js/
  content.js          → ⭐ CONTENU ÉDITABLE (montant collecté, lien formulaire, fiches équipe)
  app.js              → navigation, fiches équipe, compteurs, page don (calcul fiscal)
  clocher.js          → clocher + mur des bâtisseurs (pierres / murs)
  simulator.js        → simulateur des bâtisseurs (curseur € → pierres/murs + fiscalité)
  budget.js           → camembert budget (survol = % en relief + animation)
  jobs.js             → recrutement : fiches de mission (modale)
  faq.js              → accordéon de la foire aux questions
  fallback.js         → filet de sécurité d'affichage
assets/img/           → toutes les images en fichiers (.jpeg/.png)
og-image.jpg, sitemap.xml, robots.txt → à placer à la RACINE du domaine
```

## Modifier le contenu sans toucher au code  →  js/content.js
- Montant collecté : `raised: 1400000` (en euros). Tout se recalcule
  (clocher, pierres, murs, %, mur des bâtisseurs). Objectif : 6 000 000 € = 600 000 pierres ; 1 mur = 100 pierres.
- Lien du formulaire de contact : collez l'URL de votre Google Form dans `contactFormUrl`.
- Fiches équipe (`membres`) : `role` et `bio` par personne. `\n` = saut de ligne.
  La clé doit rester identique au `data-key` du portrait dans index.html.

## Mettre en ligne
Copiez tout le contenu de ce dossier à la racine de l'hébergement
(`index.html` à la racine), ainsi que og-image.jpg, sitemap.xml et robots.txt.

## Important
Cette version « éclatée » doit être servie par un serveur web (ou en ligne) :
les chemins css/, js/, assets/ ne fonctionnent pas en double-cliquant le fichier.
Pour un aperçu local rapide, utilisez plutôt le fichier unique sjo2-site-complet.html.
