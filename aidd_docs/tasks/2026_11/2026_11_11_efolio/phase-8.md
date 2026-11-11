---
status: pending
---

# Instruction: Cache Redis full-page à tags

## Architecture projection

```txt
.
├── ✏️ config/packages/cache.yaml
├── ✅ src/Domain/Cache/CacheTags.php
├── ✅ src/Infrastructure/Cache/CacheAsideSiteRenderer.php
├── ✅ src/Infrastructure/Cache/RedisCachedSiteRenderer.php
├── ✅ src/Application/EventListener/InvalidateCacheOnPublish.php
└── ✅ tests/Unit/Cache/RedisCachedSiteRendererTest.php
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| -------- | ------- | ------------------- | ----- |
| Setup | Page publiée FR + EN, Redis up | Cache vide | system |
| Happy path | GET / deux fois | 1er rendu BDD, 2e servi du cache | browser, cli |
| Edge case | `docker compose stop redis` puis GET / ; publication puis GET | 200 servi depuis la BDD ; hit suivant régénéré | browser |
| Teardown | Vider le cache Redis | Baseline cache vide | system |

## Tasks to do

### `1)` Décorateur Redis + fallback

> Pattern Décorateur : le site répond toujours, avec ou sans Redis.

1. `CacheAsideSiteRenderer` : rend via le moteur Twig, écrit le cache
2. `RedisCachedSiteRenderer` : décorateur try/catch → fallback sur le décoré si Redis down ; `#[AsAlias]` sur le décorateur et `#[Autowire]` du décoré dans son constructeur — aucun câblage YAML ; le contrôleur n'injecte que l'interface

### `2)` Clés + tags

> Invalidation par composant sans assemblage de fragments à runtime.

1. Clés `page:{slug}:{locale}` (V1 : 2 clés) ; tags : page + `component:{id}` de chaque composant publié
2. Vérifier le support des tags sur l'adapter Redis (sinon pool taggable dédié) — décision notée dans le dossier

### `3)` Invalidation événementielle

> Le public n'est jamais périmé après une publication.

1. `InvalidateCacheOnPublish` sur `ContentPublished` : invalide les tags ; no-op si Redis down
2. `DraftSaved` ne touche jamais le cache (le brouillon n'est pas public)
3. Régénération paresseuse au hit suivant ; warmup au déploiement (phase 10)

## Test acceptance criteria

| Task | Acceptance criteria |
| ---- | ------------------- |
| 1 | Redis stoppé → GET / 200 servi depuis la BDD, aucune erreur |
| 2 | Redis up → 2e GET servi du cache ; publication → hit suivant régénéré |
| 3 | Le test unitaire du décorateur couvre le fallback (cache en erreur) ; les noms d'événements et tags passent par les constantes |
