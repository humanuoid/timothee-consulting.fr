---
status: pending
---

# Instruction: Site public one-page + preview live

## Architecture projection

```txt
.
├── ✅ src/Domain/Service/SiteRendererInterface.php
├── ✅ src/Application/Service/SiteRenderer.php
├── ✅ src/Presentation/Controller/SiteController.php
├── ✅ src/Presentation/Controller/PreviewController.php
├── ✅ src/Presentation/Controller/SitemapController.php
├── ✅ config/routes/site.yaml
├── ✅ templates/site/base.html.twig
├── ✅ templates/site/page.html.twig
├── ✅ templates/site/_components/*.html.twig
├── ✅ assets/site.ts
├── ✅ assets/styles/site.css
├── ✅ assets/images/
├── ✅ translations/messages.fr.yaml
├── ✅ translations/messages.en.yaml
├── ✅ assets/controllers/preview_controller.ts
├── ✅ tests/Feature/Site/PublicTest.php
└── ✅ tests/Feature/Site/PreviewTest.php
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| -------- | ------- | ------------------- | ----- |
| Setup | Page publiée FR + EN (fixtures), phase 6 OK | Contenu publié en base | cli |
| Happy path | Ouvrir / puis /en/ ; cliquer les ancres ; télécharger CV et DC | 200 aux deux locales, sections ancrées, PDF 200 | browser |
| Edge case | Locale EN non publiée ; preview anonyme ; slug inconnu | EN invisible côté public (404 en /en/, bascule masquée) ; 302 ; 404 propre | browser, api |
| Teardown | — | — | — |

## Wireframe

```txt
┌───────────────────────────────────────────────────────┐
│ (1) TM · Parcours · Compétences · Projets · Contact    │
│     · CV pour RH · DC pour client ESN · (2) FR / EN    │
├───────────────────────────────────────────────────────┤
│ (3) Hero anthracite : texte gauche, photo ronde droite │
│ (4) Sections ancrées : parcours (frise), compétences,  │
│     projets (cartes), contact                           │
│ (5) Footer : mentions légales · confidentialité · année │
└───────────────────────────────────────────────────────┘
```

1. Header fixe : logo, liens d'ancres dérivés des slots (hors header), téléchargements CV/DC, bascule FR/EN.
2. Bascule de langue : / ↔ /en/.
3. Hero : direction B — titres serif dorés sur anthracite.
4. Sections rendues par les partials composants, ancrées.
5. Footer : liens vers les pages légales (livrées en phase 9) et année.

## Tasks to do

### `1)` Moteur de rendu unique

> Public et preview partagent le même moteur Twig.

1. `SiteRendererInterface` (port Domain) : `render(Page, locale, useDraft)`
2. `SiteRenderer` : published de la traduction (ou draft si preview), résolution des composants via la registry, rendu par les partials

### `2)` Routing one-page + ancres

> Une page, des ancres, pas d'arbre.

1. `/` (FR) et `/en/` (EN) → la page unique publiée ; sinon 404 propre
2. Ancres #parcours #competences #projets #contact dérivées des slots (hors header)

### `3)` Header, direction B et libellés

> Spec §3-§4 : anthracite, serif doré, frise pointillée ; Bootstrap aussi côté front.

1. `assets/site.ts` : import Bootstrap 6 (JS) via importmap ; `assets/styles/site.css` : tokens maison par-dessus les variables --bs-*
2. Header fixe : logo TM, liens d'ancres, CV/DC (media, application/pdf), bascule FR/EN — bascule masquée tant que la traduction EN n'est pas publiée
3. Illustrations statiques (fusée, personnage) servies en local dans `assets/images/`, référencées par les partials parcours et competences ; timeline pointillée + pastilles colorées + cartes blanches ; responsive (header compact mobile, colonnes empilées)
4. Libellés d'interface (liens du header, « CV pour RH », « DC pour client ESN », bascule) via `translations/` fr + en ; le contenu éditorial reste en base par traduction

### `4)` SEO

> Spec §6 : title « Timothée Consulting », meta = accroche du hero.

1. Title et meta description par traduction (metaTitle, metaDescription)
2. hreflang fr/en + attribut lang ; sitemap.xml (2 URLs) ; robots.txt

### `5)` Preview live

> Split view Sulu-like : formulaires à gauche, preview à droite.

1. `/admin/preview/{locale}` derrière auth editor : `useDraft=true`, jamais cachée, badge BROUILLON
2. Iframe preview à droite de l'éditeur (phase 6), refresh après autosave (debounce 1,5 s)

## Test acceptance criteria

| Task | Acceptance criteria |
| ---- | ------------------- |
| 1 | / et /en/ rendent la page publiée ; le brouillon n'est jamais servi au public |
| 2 | Ancres dérivées des slots ; navigation Turbo sans rechargement complet |
| 3 | CV et DC téléchargeables (200, application/pdf) ; bascule FR/EN masquée tant que la traduction EN n'est pas publiée ; direction B respectée (serif doré, anthracite, frise, illustrations servies en local) |
| 4 | hreflang présents ; sitemap.xml liste les 2 URLs ; meta description = accroche du hero |
| 5 | Preview anonyme → 302 ; brouillon visible < 2 s après autosave ; aucun accès cache en preview |
