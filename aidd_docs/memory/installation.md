# Installation — setup local from scratch

Projet : CMS Symfony maison pour le portfolio de Timothée Consulting
(`timothee-consulting.fr`). Stack : PHP 8.4, Symfony 7.4 LTS (via Symfony CLI),
MariaDB 11.4, Redis 8, Docker compose (OrbStack), assets TS via SWC (`sensiolabs/typescript-bundle`, zéro Node).

## 1. Prérequis (macOS, Apple Silicon)

```bash
brew install php@8.4 composer symfony-cli/tap/symfony-cli
brew install --cask orbstack        # daemon docker compatible (ou Docker Desktop)
```

- **Mago** (lint + format PSR-12) : install épinglée, binaire dans `~/.local/bin` :

```bash
curl --proto '=https' --tlsv1.2 -sSf https://carthage.software/mago.sh | bash -s -- --version=1.59.0
mv ./mago ~/.local/bin/mago
```

- Le fichier `.php-version` à la racine du dépôt fait choisir PHP 8.4 à la
  Symfony CLI (`symfony php -v` doit afficher 8.4.x). Aucun réglage de PATH
  n'est nécessaire pour PHP : `php` et `composer` sont déjà dans `/opt/homebrew/bin`.
- **LSP** : la Symfony CLI embarque son propre Symfony Language Tools
  (`symfony lsp:check` pour diagnostiquer, mis à jour avec `brew upgrade symfony-cli`).
  Pour OpenCode/VSCode, un binaire standalone `symfony-lsp` vit dans `~/.local/bin`
  (ajouter `export PATH="$HOME/.local/bin:$PATH"` à `~/.zshrc`) ; sa version est
  vérifiée chaque semaine par une tâche planifiée OpenChamber locale.

Vérifications :

```bash
symfony php -v        # PHP 8.4.x
symfony -v            # Symfony CLI
symfony lsp:check     # LSP embarqué de la CLI
~/.local/bin/symfony-lsp --version   # standalone local (OpenCode/VSCode)
symfony check:php     # extensions PHP requises
```

## 2. Services docker (FrankenPHP, MariaDB, Redis, DevMailer, watch TS)

Tout l'environnement local tourne dans docker compose — aucun serveur web
local à installer. `compose.yaml` = base (services + volumes), `compose.override.yaml`
= ports locaux + devmailer (convention Symfony, non déployé en prod).

- `app` : FrankenPHP, image du projet (`.docker/frankenphp/Dockerfile`, basée sur
  `dunglas/frankenphp:1.13.5-php8.4.27-alpine` épinglé + `pdo_mysql`/`redis`/etc.),
  servi en HTTPS auto sur `https://localhost` (ports 80/443 publiés par
  `compose.override.yaml`). Le `Caddyfile` custom de `.docker/frankenphp/` servira
  pour le hot reload (à brancher plus tard).
- `typescript` : `typescript:build --watch` (SWC) pour recompiler les assets TS
- `database` : MariaDB 11.4 · `redis` : Redis 8 · `devmailer` : mailpit

```bash
docker compose up -d
docker compose ps            # tous les services up/healthy
```

> **Gestion auto des environnements par worktree** (natif OpenChamber, sans
> plugin) : dans les réglages du projet (Worktrees), `setup-worktree` =
> `docker compose up -d` (lancé à la création du worktree, avant la session) et
> `teardownWorktree` = `docker compose down -v` (à la suppression du worktree).
> Le nom de projet compose = nom du dossier worktree (pas de `name:` fixe) :
> chaque worktree a ses propres conteneurs/volumes. Pas de backup de volumes en
> local (données jetables, fixtures rechargeables via `doctrine:fixtures:load`).
> Règle : un seul worktree « up » à la fois (ports 80/443 fixes).

DevMailer (mailpit) : interface web sur http://127.0.0.1:8025, SMTP sur 1025.

## 3. Application

```bash
composer install
symfony console typescript:build   # compile assets/**/*.ts (SWC, sans Node)
```

Le service `typescript` du compose tourne `typescript:build --watch` : les
contrôleurs TS de `assets/controllers/` sont recompilés à chaque changement et
servis compilés par l'asset mapper.

Configuration : `.env` (non commité) contient `DATABASE_URL` (MariaDB),
`REDIS_URL`, `MAILER_DSN` (devmailer). Valeurs par défaut commitées dans
`.env.example`. La Symfony CLI remplace automatiquement les hôtes
`database`/`redis`/`devmailer` par `127.0.0.1:<port publié>` en local.

Migrations :

```bash
symfony console doctrine:migrations:migrate --no-interaction
```

## 4. Ouvrir l'application

Le serveur web est le conteneur FrankenPHP lancé à l'étape 2 :

```bash
# https://localhost  (http://localhost redirige 308 vers https, certificat local auto)
```

Les assets TS sont recompilés à la volée par le service `typescript` (watch SWC).
Hot reload navigateur : prévu pour plus tard, en branchant le `Caddyfile` custom
(`.docker/frankenphp/Caddyfile`) avec `hot_reload` + Mercure.

## 5. Health check

```bash
curl -sk https://localhost/health | jq
# { "php": "8.4.27", "bdd": "ok", "redis": "ok", "time": "..." }

symfony console app:health    # même rapport en CLI (PHP 8.4 via .php-version)
```

PHP est identique partout : 8.4.27 (`.php-version` côté CLI, image FrankenPHP
`1.13.5-php8.4.27` côté serveur).

Redis stoppé (`docker compose stop redis`) : `/health` répond toujours 200 avec
`"redis": "down"` — le site fonctionne sans Redis (fallback BDD).

## 6. Arrêt propre

```bash
docker compose down
```
