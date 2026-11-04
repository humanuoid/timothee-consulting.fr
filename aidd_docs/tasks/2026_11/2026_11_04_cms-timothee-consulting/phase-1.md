---
status: pending
---

# Instruction: Socle Symfony 7 + environnement o2switch (un seul dépôt git, MariaDB, Redis)

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ .gitignore
├── ✅ .env
├── ✅ .env.example
├── ✅ README.md
├── ✅ bin/console
├── ✅ composer.json
├── ✅ config/packages/cache.yaml
├── ✅ config/packages/doctrine.yaml
├── ✅ config/routes.yaml
├── ✅ src/Command/HealthCommand.php
└── ✅ src/Controller/HealthController.php
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Créer la base MariaDB + utilisateur via cPanel o2switch, remplir `.env.local` (DATABASE_URL, REDIS_URL) | Base créée, `.env.local` présent et non commité | system |
| Happy path | `php bin/console app:health` puis ouvrir `/health` sur `admin.…` | JSON 200 avec `bdd: ok`, `redis: ok`, version PHP | cli, browser |
| Edge case | DATABASE_URL avec un mauvais mot de passe | `app:health` échoue avec une erreur claire | cli |
| Teardown | Supprimer `.env.local` de test, réinitialiser la base | Retour à l'état initial, aucun secret persisté | system |

## Tasks to do

### `1)` Créer le dépôt git unique et le squelette Symfony

> Un seul repo pour code + config ; le contenu vivra en BDD, pas de submodule.

1. `git init` + remote GitHub privé `timothee-consulting`
2. `composer create-project symfony/website-skeleton` (Symfony 7 LTS) à la racine du repo
3. Commit initial

### `2)` Configurer MariaDB et Redis

> o2switch fournit les deux nativement ; rien à installer côté serveur.

1. Créer la base + l'utilisateur MariaDB dans cPanel, noter le host
2. `DATABASE_URL` dans `.env.local` ; `cache.app` sur Redis dans `config/packages/cache.yaml` + `REDIS_URL`
3. `.env.local` dans `.gitignore`, `.env.example` commité avec des placeholders

### `3)` Health check

> Prouver que PHP, la BDD et Redis répondent avant toute fonctionnalité.

1. `HealthCommand` (`app:health`) : ping Doctrine + ping Redis + `phpversion()`
2. `HealthController` : route `/health` en JSON

### `4)` Déploiement initial sur o2switch

> Le dashboard vivra sur le sous-domaine `admin.timothee-consulting.fr`.

1. Cloner le repo en SSH sur o2switch, `composer install --no-dev`
2. Pointer `admin.…` sur `public/` (document root), activer SSL Let's Encrypt via cPanel
3. Droits en écriture sur `var/` et `public/uploads/`

### `5)` README de déploiement

> Un tiers doit pouvoir redéployer from scratch.

1. Procédure : création BDD cPanel, `.env.local`, composer install, droits, cron

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | Le repo existe avec un commit initial contenant le squelette Symfony |
| 2 | `doctrine:query:sql "SELECT 1"` et le ping Redis répondent OK depuis le serveur |
| 3 | `/health` renvoie 200 avec `bdd: ok` et `redis: ok` |
| 4 | `https://admin.timothee-consulting.fr/health` répond en HTTPS depuis Internet |
| 5 | Le README permet de redéployer le projet en suivant uniquement ses étapes |
