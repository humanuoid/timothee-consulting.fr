---
status: pending
---

# Instruction: Preview live (même moteur Twig) + versioning par snapshots + cache Redis (admin et full-page public)

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ src/Controller/Admin/PreviewController.php
├── ✅ src/Service/VersioningService.php
├── ✅ src/Service/RenderCache.php
├── ✅ src/Controller/Admin/VersionController.php
├── ✅ templates/admin/page/preview.html.twig
├── ✅ templates/admin/page/versions.html.twig
├── ✅ assets/controllers/preview_controller.js
└── ✅ config/packages/cache.yaml
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Page avec brouillon modifié (phase 6), connecté en editor | Preview accessible | browser |
| Happy path | Modifier un bloc → autosave → observer la preview | L'iframe reflète le brouillon en moins de 2 s, badge "BROUILLON" | browser |
| Edge case | Restaurer une ancienne version ; consulter le public après invalidation | Le brouillon revient au snapshot ; le public recalcule depuis la BDD | browser, cli |
| Teardown | Supprimer les versions de test, vider le cache Redis | Historique et cache revenus à la baseline | system |

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
2. Badge "BROUILLON" tant que la page n'est pas publiée.
3. Lien vers l'historique des versions.
4. Éditeur de blocs (phase 6).
5. Preview : le vrai template public rendu avec le brouillon, dans une iframe rafraîchie automatiquement.

## Tasks to do

### `1)` Route de preview

> Même moteur que le public : rendu exact garanti.

1. `PreviewController` : `/admin/preview/{page}` → `SiteRenderer::render($page, useDraft: true)` → HTML pour l'iframe
2. `#[IsGranted]` editor/admin ; jamais exposé sur le domaine public

### `2)` Preview live dans l'éditeur

> L'iframe se rafraîchit après chaque autosave.

1. `edit.html.twig` : colonne preview = iframe vers `/admin/preview/{id}` + badge BROUILLON
2. Après autosave OK, `preview_controller` recharge l'iframe (debounce 1,5 s)

### `3)` Versioning par snapshots

> Compense l'absence de git sur le contenu (passage en BDD).

1. `VersioningService::snapshot(Page, label, author)` : copie `draft` (ou `published`) dans `ContentVersion`
2. Snapshot auto à chaque autosave (label "autosave") et à chaque publication (label "publication vN")
3. `/admin/pages/{id}/versions` : liste des versions + restauration (écrit le snapshot dans `draft`)

### `4)` Cache Redis : admin + full-page public

> Un seul Redis o2switch pour les deux. La preview (draft) n'est jamais servie depuis le cache public.

1. `RenderCache` : sert le HTML `published` de `SiteController` depuis Redis, clés taggées `page:{slug}`
2. `cache.app` sur Redis avec préfixe ; invalidation par tags à chaque publication (phase 8) et à chaque autosave pour la preview
3. La preview reste calculée à la volée (clé séparée `preview:{id}:{md5(draft)}`)

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | `/admin/preview/accueil` rend le contenu brouillon ; inaccessible sans login |
| 2 | Après autosave, l'iframe affiche la modification sans clic manuel |
| 3 | Chaque autosave crée une ContentVersion ; restaurer une version ramène le contenu exact |
| 4 | Après invalidation du tag `page:accueil`, `GET /` recalcule depuis la BDD (pas de HTML périmé servi) |
