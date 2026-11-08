---
status: pending
---

# Instruction: Preview live (même Twig) + versioning + cache Redis en décorateur (fallback BDD, site OK sans Redis) + event-driven

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ src/Domain/Event/ContentPublished.php
├── ✅ src/Domain/Event/DraftSaved.php
├── ✅ src/Domain/Cache/CacheTags.php
├── ✅ src/Application/Service/VersioningService.php
├── ✅ src/Application/EventListener/InvalidateCacheOnPublish.php
├── ✅ src/Infrastructure/Cache/RedisCachedSiteRenderer.php
├── ✅ src/Infrastructure/Cache/CacheAsideSiteRenderer.php
├── ✅ src/Presentation/Controller/Admin/PreviewController.php
├── ✅ src/Presentation/Controller/Admin/VersionController.php
├── ✅ templates/admin/page/preview.html.twig
├── ✅ templates/admin/page/versions.html.twig
├── ✅ assets/controllers/preview_controller.ts
└── ✅ config/packages/cache.yaml
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Brouillon modifié (phase 6), connecté | Preview accessible | browser |
| Happy path | Modifier un bloc → autosave → observer la preview | L'iframe reflète le brouillon en < 2 s, badge "BROUILLON" | browser |
| Edge case | Redis stoppé (`docker compose stop redis`) puis GET `/` | Le site répond depuis la BDD, aucune erreur | browser |
| Teardown | Vider le cache Redis, restore version de test | Cache et contenu revenus à la baseline | system |

## Wireframe

```txt
┌───────────────────────────────────────────────┐
│ (1) Barre éditeur · badge (2) "BROUILLON" ·   │
│     (3) "Voir l'historique"                   │
├───────────────────────────┬───────────────────┤
│ (4) Éditeur de blocs      │ (5) Preview       │
│                           │  iframe : rendu   │
│                           │  Twig du          │
│                           │  brouillon,       │
│                           │  rafraîchi à chaque│
│                           │  autosave         │
└───────────────────────────┴───────────────────┘
```

1. Barre de l'éditeur.
2. Badge "BROUILLON" tant que non publié.
3. Lien vers l'historique des versions.
4. Éditeur de blocs (phase 6).
5. Preview : le vrai template public rendu avec le brouillon, iframe rafraîchie automatiquement.

## Tasks to do

### `1)` Route de preview

> Même moteur Twig que le public : rendu exact garanti.

1. `PreviewController` : `/admin/preview/{page}` → `SiteRenderer::render($page, useDraft: true)`
2. `#[IsGranted]` editor/admin ; jamais exposé sur le domaine public
3. `preview_controller.ts` recharge l'iframe après chaque autosave (debounce 1,5 s)

### `2)` Versioning par snapshots

> Compense l'absence de git sur le contenu.

1. `VersioningService::snapshot(Page, label, author)` → `ContentVersion`
2. Snapshot auto à chaque autosave (`DraftSaved`) et à la publication (`ContentPublished`)
3. `/admin/pages/{id}/versions` : liste + restauration (écrit le snapshot dans `draft`)

### `3)` Event-driven : événements + listeners

> Pattern Constant pour les noms d'événements et tags.

1. `Domain/Event/ContentPublished.php`, `DraftSaved.php` ; `Domain/Cache/CacheTags.php` (constantes `page:{slug}`)
2. `InvalidateCacheOnPublish` : à `ContentPublished`, invalide les tags Redis (no-op si Redis down)
3. Câblage dans `config/services.yaml` (autoconfigure événements)

### `4)` Cache Redis en décorateur, fallback BDD

> Pattern Décorateur : le site fonctionne sans Redis.

1. `CacheAsideSiteRenderer` (Infrastructure) : lit la BDD, écrit le cache
2. `RedisCachedSiteRenderer` : décorateur au-dessus, try/catch Redis → fallback sur le décoré si Redis down
3. Le contrôleur public appelle toujours l'interface (jamais Redis en direct)
4. `cache.app` sur Redis avec préfixe ; preview jamais servie depuis le cache public

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | `/admin/preview/accueil` rend le brouillon ; inaccessible sans login ; iframe refresh auto après autosave |
| 2 | Chaque autosave/publication crée une `ContentVersion` ; restore ramène le contenu exact |
| 3 | `ContentPublished` déclenche l'invalidation des tags Redis ; noms d'événements/tags via constantes |
| 4 | Redis up : `GET /` servi depuis le cache ; Redis down : `GET /` répond depuis la BDD sans erreur |
