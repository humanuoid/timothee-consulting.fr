---
status: pending
---

# Instruction: Front public multi-pages (Twig) + assets Stimulus/TypeScript + Turbo + formulaire de contact

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ templates/site/base.html.twig
├── ✅ templates/site/page.html.twig
├── ✅ templates/site/_partials/hero.html.twig
├── ✅ templates/site/_partials/parcours.html.twig
├── ✅ templates/site/_partials/competences.html.twig
├── ✅ templates/site/_partials/projets.html.twig
├── ✅ templates/site/_partials/contact.html.twig
├── ✅ templates/site/contact/stream.html.twig
├── ✅ src/Application/Service/SiteRenderer.php
├── ✅ src/Presentation/Controller/SiteController.php
├── ✅ src/Presentation/Controller/ContactController.php
├── ✅ src/Presentation/Form/ContactType.php
├── ✅ assets/site.ts
├── ✅ assets/controllers/contact_controller.ts
├── ✅ service compose `typescript` (watch SWC, posé en phase 1)
├── ✅ public/assets/site.css
└── ✅ public/assets/favicon.svg
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Contenu seedé (phase 2), serveur local lancé | Page accueil en BDD | cli |
| Happy path | Ouvrir `/` et `/accueil` ; soumettre le contact valide | HTML complet des sections ; Turbo Stream "Message envoyé" + mail dans DevMailer | browser |
| Edge case | `/{slug}` inconnu ou brouillon ; contact avec honeypot rempli | 404 propre ; aucun mail, réponse 200 neutre | browser |
| Teardown | `symfony console asset-map:compile` propre | `public/assets/` régénéré | system |

## Wireframe

```txt
┌───────────────────────────────────────────────┐
│ (1) Header : T|M · nav (Accueil, À propos,    │
│     Projets, Contact) — Turbo Drive actif     │
├───────────────────────────────────────────────┤
│ (2) Contenu de la page courante :             │
│     blocs rendus (hero, parcours, …)         │
├───────────────────────────────────────────────┤
│ (3) Contact : (4) formulaire nom·email·msg    │
│ · (5) footer : mentions, année               │
└───────────────────────────────────────────────┘
```

1. Header fixe multi-pages : logo + nav, interceptée par Turbo Drive.
2. Corps de la page : les blocs publiés, rendus par le partial adapté.
3. Section contact avec formulaire.
4. Formulaire : nom, email, message + honeypot caché + CSRF.
5. Footer : mentions, année.

## Tasks to do

### `1)` Moteur de rendu unique

> Public, preview et admin partagent ce moteur Twig.

1. `SiteRenderer::render(Page $page, bool $useDraft)` : choisit draft/published, rend `site/page.html.twig` → HTML
2. Rendu des blocs via partials `_partials/{type}.html.twig`

### `2)` Contrôleur public multi-pages

> Le site n'est pas une one-page : `/` = accueil, `/{slug}` = page publiée.

1. `SiteController` : `/` et `/{slug}` → pages `published` uniquement, 404 sinon
2. ETag / Cache-Control pour le cache full-page (phase 7)

### `3)` Templates et design

> Multi-pages, inspiré de emonin.dev, responsive.

1. `base.html.twig` : head (SEO, favicon), header nav multi-pages, footer
2. `page.html.twig` + partials par type de bloc
3. `public/assets/site.css` : variables CSS (tokens), design épuré, responsive 360 px

### `4)` Assets Stimulus + TypeScript

> Uniquement Stimulus et TS, compilés par SWC via l'asset mapper (sensiolabs/typescript-bundle, zéro Node).

1. Compilation TS : `symfony console typescript:build` (watch actif en dev via le service compose `typescript`)
2. AssetMapper + importmap pour Stimulus/Turbo ; `assets/site.ts` importe Turbo Drive
3. Contrôleurs Stimulus écrits en TS (`assets/controllers/*.ts`)

### `5)` Formulaire de contact sécurisé

> Le public exécute du PHP : surface minimale.

1. `ContactType` + `ContactController` : POST seul, CSRF, rate limiter 3/min, honeypot
2. Mail via Mailer (DevMailer local, SMTP o2switch prod), réponse Turbo Stream

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | `SiteRenderer` avec `useDraft=true` reflète le brouillon, `false` non ; un bloc de chaque type rend sans erreur |
| 2 | `/` et `/accueil` rendent la page publiée ; slug inconnu ou brouillon → 404 |
| 3 | `symfony console typescript:build` compile `assets/**/*.ts` ; navigation entre pages sans rechargement complet (Turbo) |
| 4 | CSS chargé, lisible à 360 px, tokens en variables CSS |
| 5 | POST contact valide → Turbo Stream "Message envoyé" + mail visible dans DevMailer ; honeypot rempli → aucun mail |
