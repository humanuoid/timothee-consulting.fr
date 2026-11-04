---
status: pending
---

# Instruction: Mise en production — publication instantanée + invalidation du cache Redis + domaine public sur o2switch

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ src/Controller/Admin/PublishController.php
├── ✅ src/Service/PublishService.php
├── ✅ config/packages/http_cache.yaml
├── ✅ public/.htaccess
├── ✅ deploy.sh
└── ✅ docs/deploiement-prod.md
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Preprod WP en ligne, admin déployé, contenu importé | `admin.…` OK, domaine public pas encore switché | system |
| Happy path | Modifier un bloc → cliquer "Publier" → recharger la home publique | Nouveau contenu visible immédiatement (tag Redis invalidé) | browser |
| Edge case | Publier sans aucune modification du brouillon | Message "déjà à jour", aucune invalidation | browser |
| Teardown | Restaurer la version précédente via l'historique | Le public revient au contenu précédent | browser, cli |

## Tasks to do

### `1)` Bouton Publier : flip + snapshot + invalidation

> La publication est instantanée : plus de build, plus de génération.

1. `PublishController` : POST `/admin/pages/{id}/publish` → `PublishService::publish` copie `draft` vers `published`, status=published, publishedAt=now, snapshot "publication vN"
2. Invalide les tags Redis `page:{slug}` (et la home) → le prochain GET recalcule depuis la BDD
3. Flash "Page publiée — en ligne immédiatement", badge passe à "Publié"

### `2)` Cache full-page côté public

> Régime permanent quasi statique ; seul le premier hit après invalidation est dynamique.

1. Branche `RenderCache` (Redis, phase 7) dans `SiteController` pour les pages `published`
2. Headers `Cache-Control` + ETag ; `http_cache.yaml` configuré (HttpCache Symfony en filet de sécurité)
3. Jamais de cache pour `/admin` ni pour la preview

### `3)` Mise en production des deux domaines

> Remplacer la preprod WordPress.

1. `timothee-consulting.fr` → document root = `public/` du projet Symfony (site public dynamique) ; `admin.…` reste le dashboard
2. SSL Let's Encrypt sur les deux domaines ; `.htaccess` : réécritures, cache navigateur 1 an sur assets, 404 custom
3. `deploy.sh` : clone/pull + composer install + warmup du cache Redis

### `4)` Supervision

> Plus de cron de génération ; juste la santé du cache.

1. Cron o2switch : ping Redis + `app:health`, logs dans `var/log/`
2. Alerting simple si Redis down (le site reste fonctionnel, juste non caché)

### `5)` Vérifications finales

> Avant d'annoncer la mise en production.

1. Lighthouse cache chaud : perf visée ≥ 90 (régime full-page cache)
2. Navigation Turbo entre les ancres sans rechargement ; formulaire de contact OK ; 404 propre
3. Procédure de rollback documentée : restore de version + invalidation du tag Redis

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | Publier copie draft → published, crée le snapshot, invalide le tag `page:{slug}`, affiche la confirmation |
| 2 | Après invalidation, `GET /` recalcule depuis la BDD ; avant invalidation, sert le HTML caché |
| 3 | `https://timothee-consulting.fr` sert le site en HTTPS ; `admin.…` sert le dashboard |
| 4 | Le cron tourne sans erreur (logs), Redis répond au ping |
| 5 | Lighthouse ≥ 90 cache chaud ; rollback testé et documenté |
