---
status: pending
---

# Instruction: Déploiement o2switch + contenu réel

## Architecture projection

```txt
.
├── ✅ tools/deploy/main.go
├── ✅ tools/deploy/go.mod
├── ✅ tools/deploy/deploy_test.go
├── ✅ deploy/server/post-receive
├── ✅ docs/deploiement-prod.md
└── ✏️ .github/workflows/health.yaml
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| -------- | ------- | ------------------- | ----- |
| Setup | Clés SSH o2switch, repo bare initialisé, staging configuré | Connexion SSH OK | system |
| Happy path | `go run ./tools/deploy --env staging` puis prod | Nouveau commit servi ; /health 200 ; contact → mail reçu via cron | cli, browser |
| Edge case | Tests locaux rouges ; Redis down en prod | Déploiement refusé ; site répond sans cache | cli, browser |
| Teardown | `--ref <sha>` rollback | Commit précédent servi | cli |

## Tasks to do

### `1)` CLI Go de déploiement

> Depuis la machine du propriétaire uniquement ; jamais de rsync.

1. Pré-checks locaux : pest, mago lint, mago fmt --check, phpstan, lint:yaml/twig/container, security:check — déploiement bloqué si rouge
2. Push SSH vers le repo bare o2switch ; attente du hook ; health check post-déploiement avec retries
3. `--ref <sha>` pour le rollback ; tests Go (`deploy_test.go`)

### `2)` Hook post-receive

> Séquence de la doc o2switch.

1. Checkout `GIT_WORK_TREE` vers le docroot, `composer install --no-dev`, `doctrine:migrations:migrate --no-interaction`, `cache:clear`, `cache:warmup`

### `3)` Staging preprod.timothee-consulting.fr

> On éprouve le pipeline avant la prod.

1. cPanel : domaine preprod → racine `public/` du déploiement, SSL Let's Encrypt
2. Premier déploiement via le CLI Go ; /health 200 ; rollback testé
3. MariaDB o2switch : version réelle vérifiée (doc : 10.6) et `serverVersion` du DSN ajusté ; mot de passe alphanumérique ; MAILER_DSN URL-encodé (@ et +)

### `4)` Cron Messenger

> Pattern doc o2switch : time-limit < intervalle.

1. Cron cPanel chaque minute : `php /home/<compte>/efolio/bin/console messenger:consume --time-limit=55 --memory-limit=128M async > ~/logs/messenger.log 2>&1`

### `5)` Prod timothee-consulting.fr

> Un seul domaine ; l'admin vit sur /admin.

1. cPanel : domaine → `public/`, SSL, déploiement final via le CLI Go
2. `.github/workflows/health.yaml` : ping `https://timothee-consulting.fr/health` à 3h00

### `6)` Contenu réel

> La ressaisie éprouve le moteur.

1. Saisie du contenu spec §3 via le backoffice : FR puis EN (textes verbatim)
2. Assets fournis par le propriétaire : portrait, fusée, personnage, logos formations, PDF CV et DC

### `7)` Recette finale

> Avant annonce.

1. Lighthouse perf et a11y ≥ 90 ; audit réseau : zéro requête externe au runtime
2. CV/DC, contact end-to-end via le cron, 404, hreflang, navigation Turbo — vérifiés sur prod

## Test acceptance criteria

| Task | Acceptance criteria |
| ---- | ------------------- |
| 1 | Tests rouges → déploiement refusé ; `go test ./tools/deploy` vert |
| 2 | Le hook exécute la séquence sans erreur, logs lisibles |
| 3 | Staging sert le nouveau commit ; rollback testé ; serverVersion DSN vérifié |
| 4 | Message de contact en prod → mail reçu ≤ 1 min via le cron |
| 5 | Prod HTTPS ; /health 200 {"status":"ok"} ; cron GitHub Actions 3h00 vert |
| 6 | Contenu spec §3 en ligne FR/EN, assets en place |
| 7 | Lighthouse ≥ 90 ; zéro requête externe au runtime |
