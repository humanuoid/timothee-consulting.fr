---
status: done
---

# Instruction: Socle via Symfony CLI + environnement local Docker + CI GitHub Actions

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ .env
├── ✅ .env.example
├── ✅ AGENTS.md
├── ✅ INSTALL.md
├── ✅ README.md
├── ✅ bin/console
├── ✅ compose.yaml
├── ✅ composer.json
├── ✅ config/packages/cache.yaml
├── ✅ config/packages/doctrine.yaml
├── ✅ config/packages/sensiolabs_typescript.yaml
├── ✅ .docker/frankenphp/Dockerfile
├── ✅ .docker/frankenphp/Caddyfile
├── ✅ src/Application/.gitkeep
├── ✅ src/Command/HealthCommand.php
├── ✅ src/Controller/HealthController.php
├── ✅ src/Domain/.gitkeep
├── ✅ src/Infrastructure/.gitkeep
├── ✅ src/Presentation/.gitkeep
├── ✅ .github/workflows/ci.yaml
├── ✅ .github/workflows/health.yaml
└── ✅ .github/workflows/security.yaml
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | `brew install php@8.4` + Symfony CLI + LSP standalone, `docker compose up -d` | Outils installés, conteneurs frankenphp/mariadb/redis/devmailer/typescript up | system |
| Happy path | `symfony new` (pas composer create-project), `docker compose up -d`, ouvrir `https://localhost/health` | Projet Symfony créé, `/health` répond 200 avec `bdd: ok`, `redis: ok` | cli, browser |
| Edge case | `docker compose stop redis` puis `/health` | Réponse 200 avec `redis: down` — le site fonctionne sans Redis | browser |
| Teardown | `docker compose down` | Environnement local arrêté proprement | system |

## Tasks to do

### `1)` Toolchain locale

> PHP via Homebrew, Symfony CLI, LSP standalone branché à OpenCode.

1. `brew install php@8.4` (ou php), vérifier `php -v` = 8.4
2. Installer Symfony CLI, vérifier `symfony -v`
3. Installer Symfony LSP standalone et le déclarer comme LSP externe d'OpenCode

### `2)` Créer le projet avec Symfony CLI

> Jamais `composer create-project`.

1. `symfony new timothee-consulting.fr --webapp` dans le repo (ou à la racine si vide) — Symfony 7.4 LTS
2. Poser la structure DDD : `src/Domain`, `src/Application`, `src/Infrastructure`, `src/Presentation` (+ .gitkeep)
3. `composer.json` propre, branche `main`, dépôt public MIT déjà en place

### `3)` Environnement local Docker

> `compose.yaml` aux conventions Symfony : services `app` (image du projet `.docker/frankenphp/Dockerfile`), `typescript` (watch SWC), `database` (mariadb), `redis` ; `compose.override.yaml` publie les ports 80/443 + `devmailer`.

1. `compose.yaml` + `compose.override.yaml` + `.docker/frankenphp/{Dockerfile,Caddyfile}`
2. `.env` : `DATABASE_URL` et `REDIS_URL` vers les services compose (résolus dans le réseau docker) ; `.env.example` commité
3. `docker compose up -d` → vérifier les 5 conteneurs

### `4)` Health check

> Prouver PHP, MariaDB, Redis (optionnel).

1. `HealthCommand` (`app:health`) : ping Doctrine, ping Redis avec try/catch (jamais de 500 si Redis down), `phpversion()`
2. `HealthController` : `GET /health` en JSON

### `5)` CI GitHub Actions

> Trois workflows.

1. `.github/workflows/ci.yaml` : sur push/PR — lint yaml/twig/container + phpunit
2. `.github/workflows/security.yaml` : cron hebdomadaire — `symfony security:check`
3. `.github/workflows/health.yaml` : cron quotidien 3h00 — curl `https://admin.timothee-consulting.fr/health`, alerte si non-200

### `6)` README + INSTALL.md

> Setup local rejouable from scratch.

1. Procédure : brew (php@8.4, symfony cli, lsp), docker compose up, `composer install`, migrations — doc vivante dans `aidd_docs/memory/installation.md`

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | `php -v` affiche 8.4, `symfony -v` OK, LSP Symfony visible dans la config LSP d'OpenCode |
| 2 | Le squelette Symfony 7.4 est à la racine avec les 4 dossiers DDD sous `src/` |
| 3 | `docker compose ps` montre database, redis, devmailer ; devmailer accessible sur son port web |
| 4 | `/health` = 200 JSON avec `bdd: ok`, `redis: ok` ; redis stoppé → 200 avec `redis: down` |
| 5 | Les 3 workflows existent et sont visibles dans l'onglet Actions de GitHub |
| 6 | Un tiers suit INSTALL.md et obtient un `/health` 200 en local |
