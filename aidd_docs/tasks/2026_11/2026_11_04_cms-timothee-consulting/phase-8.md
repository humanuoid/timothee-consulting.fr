---
status: pending
---

# Instruction: Déploiement par CLI Go + SSH (repo bare o2switch, hook post-receive) + publication instantanée + mise en prod

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ tools/deploy/main.go
├── ✅ tools/deploy/go.mod
├── ✅ deploy/server/post-receive
├── ✅ src/Application/Service/PublishService.php
├── ✅ src/Domain/Event/ContentPublished.php
├── ✅ src/Presentation/Controller/Admin/PublishController.php
├── ✅ docs/deploiement-prod.md
├── ✅ public/.htaccess
└── ✅ .github/workflows/health.yaml
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Machine du propriétaire avec clés SSH vers o2switch, repo bare initialisé sur le serveur | Connexion SSH OK | system |
| Happy path | `go run ./tools/deploy --env prod` puis modifier un bloc et Publier | Prod à jour après le déploiement ; contenu publié visible immédiatement sur le public | cli, browser |
| Edge case | Redis down en prod ; déploiement avec tests en échec | Le site répond sans cache ; le CLI Go bloque le déploiement si les tests échouent | cli |
| Teardown | Rollback : `go run ./tools/deploy --env prod --ref <sha-précédent>` | La prod revient au commit précédent | cli |

## Tasks to do

### `1)` CLI Go de déploiement

> Orchestration depuis la machine du propriétaire uniquement.

1. `tools/deploy/main.go` : pré-checks (tests phpunit OK), puis `git push ssh://o2switch/~/git/timothee-consulting.fr.git main`
2. Attente du hook, puis health check post-déploiement (`GET admin.…/health`)
3. Option `--ref <sha>` pour rollback (re-push d'un commit antérieur)
4. Aucun rsync, aucune copie de fichiers local → serveur

### `2)` Hook post-receive sur o2switch

> Le serveur déploie à la réception du push.

1. `deploy/server/post-receive` : checkout du repo bare vers le docroot (`GIT_WORK_TREE`), `composer install --no-dev`, `doctrine:migrations:migrate --no-interaction`, `cache:clear`, warmup cache Redis
2. Repo bare créé en SSH : `git init --bare ~/git/timothee-consulting.fr.git`
3. Pas de cron o2switch

### `3)` Publication instantanée

> Bouton Publier = flip + événement + invalidation.

1. `PublishService::publish(Page)` : copie `draft` → `published`, status, publishedAt, snapshot "publication vN", dispatch `ContentPublished`
2. `PublishController` : POST `/admin/pages/{id}/publish`, flash "Publié — en ligne immédiatement"
3. L'événement invalide les tags Redis (phase 7) : le public est à jour sans action

### `4)` Mise en production des domaines

> Remplacer la preprod WordPress.

1. `timothee-consulting.fr` → docroot du déploiement (site public Symfony) ; `admin.…` → même déploiement, préfixe `/admin`
2. SSL Let's Encrypt (cPanel) sur les deux domaines ; `.htaccess` : réécritures, cache navigateur 1 an sur assets, 404 custom
3. Premier déploiement via le CLI Go, vérification `admin.…/health` = 200

### `5)` Vérifications finales

> Avant annonce.

1. Lighthouse sur la home (cache Redis chaud) : perf visée ≥ 90
2. Navigation Turbo entre les pages sans rechargement ; contact OK ; 404 propre
3. Health quotidien 3h00 via GitHub Actions (workflow phase 1) ; rollback documenté dans `docs/deploiement-prod.md`

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | `go run ./tools/deploy --env prod` pousse en SSH et la prod affiche le nouveau commit après le hook |
| 2 | Le hook exécute composer install + migrations + cache:clear sans erreur (logs lisibles) |
| 3 | Publier copie draft → published, crée le snapshot, dispatche l'événement, invalide le tag Redis |
| 4 | Les deux domaines répondent en HTTPS ; `/health` = 200 avec bdd et redis OK |
| 5 | Tests en échec → déploiement bloqué ; Lighthouse ≥ 90 ; rollback testé |
