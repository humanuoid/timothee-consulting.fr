---
status: pending
---

# Instruction: Front public dynamique (Twig) + Turbo + formulaire de contact + moteur de rendu partagé

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ templates/site/base.html.twig
├── ✅ templates/site/home.html.twig
├── ✅ templates/site/page.html.twig
├── ✅ templates/site/_partials/hero.html.twig
├── ✅ templates/site/_partials/parcours.html.twig
├── ✅ templates/site/_partials/competences.html.twig
├── ✅ templates/site/_partials/projets.html.twig
├── ✅ templates/site/_partials/contact.html.twig
├── ✅ templates/site/contact/stream.html.twig
├── ✅ src/Service/SiteRenderer.php
├── ✅ src/Controller/SiteController.php
├── ✅ src/Controller/ContactController.php
├── ✅ src/Form/ContactType.php
├── ✅ config/packages/mailer.yaml
├── ✅ assets/site.js
├── ✅ public/assets/site.css
└── ✅ public/assets/favicon.svg
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Contenu seedé (phase 2), serveur lancé | Page accueil présente en BDD | cli |
| Happy path | Ouvrir `/` ; soumettre le formulaire de contact valide | HTML complet des 5 sections ; Turbo Stream "Message envoyé" + mail reçu | browser |
| Edge case | Slug inconnu ; contact avec honeypot rempli | 404 propre ; aucun mail envoyé, réponse 200 neutre | browser |
| Teardown | Vider le cache de rendu | Le rendu est recalculé depuis la BDD | system |

## Wireframe

```txt
┌───────────────────────────────────────────────┐
│ (1) Header : T|M · nav (Parcours, Skills,     │
│     Projets, Contact) — Turbo Drive actif     │
├───────────────────────────────────────────────┤
│ (2) Hero : accroche + photo + CTA             │
├───────────────────────────────────────────────┤
│ (3) Parcours : frise chronologique            │
├───────────────────────────────────────────────┤
│ (4) Compétences : grille de cartes            │
├───────────────────────────────────────────────┤
│ (5) Projets : cartes image + description      │
├───────────────────────────────────────────────┤
│ (6) Contact : (7) formulaire nom·email·msg    │
│ · (8) footer : mentions, année               │
└───────────────────────────────────────────────┘
```

1. Header fixe : logo + navigation ancrée, interceptée par Turbo Drive.
2. Hero : accroche, photo, citation, CTA "Me contacter".
3. Parcours : frise chronologique des expériences.
4. Compétences : cartes (pilotage, ERP, formation).
5. Projets : cartes avec image, description, lien.
6. Contact : email, téléphone, réseaux.
7. Formulaire : nom, email, message + honeypot caché.
8. Footer : mentions, année.

## Tasks to do

### `1)` Design des templates public

> Reprendre l'esprit de la preprod WordPress, en propre et responsive.

1. `base.html.twig` : head (meta, SEO, favicon), header nav, footer, chargement de `site.js`
2. `home.html.twig` / `page.html.twig` + partials par type de bloc
3. `public/assets/site.css` : design épuré, responsive mobile

### `2)` Moteur de rendu unique

> La preview admin et le public utilisent CE moteur : preview = rendu exact.

1. `SiteRenderer::render(Page $page, bool $useDraft)` : choisit draft ou published, rend le template Twig → string HTML
2. Gestion des médias : chemins `/uploads/...`

### `3)` Contrôleur public

> Le site public est servi en dynamique par Symfony (cache Redis branché en phase 7).

1. `SiteController` : `/` et `/{slug}` rendent les pages `published` via SiteRenderer, 404 si brouillon ou inconnu
2. Réponses avec ETag / Cache-Control pour le cache full-page

### `4)` Formulaire de contact sécurisé

> Le public exécute du PHP : surface minimale.

1. `ContactType` + `ContactController` : POST uniquement, CSRF, rate limiter (3/min), honeypot
2. Envoi via Mailer (SMTP o2switch), réponse Turbo Stream "Message envoyé"

### `5)` Turbo côté public

> Navigation sans rechargement, zéro JS custom.

1. `assets/site.js` : `import { Turbo } from '@hotwired/turbo'` servi via AssetMapper (importmap, pas de build Node)
2. Turbo Drive actif sur `base.html.twig` ; Turbo Stream pour la réponse du contact

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | `/` affiche les 5 sections avec le contenu seedé, lisible à 360 px de large |
| 2 | `SiteRenderer` avec `useDraft=true` reflète les modifs brouillon, `false` non |
| 3 | `/{slug}` inconnu → 404 ; `/` rend la page published uniquement |
| 4 | POST contact valide → Turbo Stream "Message envoyé" + mail reçu ; honeypot rempli → aucun mail |
| 5 | Clic sur un lien interne → navigation Turbo (pas de rechargement complet de page) |
