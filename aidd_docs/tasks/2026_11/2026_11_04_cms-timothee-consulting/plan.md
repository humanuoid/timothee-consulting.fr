---
objective: "Livrer un CMS Symfony maison en DDD event-driven (dashboard sécurisé TOTP avec brouillons, preview live et publication instantanée) qui sert le portfolio multi-pages de Timothée Consulting, avec cache full-page Redis en décorateur (site fonctionnel sans Redis), déployé sur o2switch par SSH depuis la machine du propriétaire."
status: pending
---

# Plan: CMS Symfony DDD + site public dynamique caché (Redis) pour le portfolio Timothée Consulting

Source: Conversation OpenChamber 2026-11-04 → 2026-11-08 (session courante, répertoire `/Users/emmanuel/Coding/timothee-consulting.fr`). Spécifications verrouillées par l'utilisateur le 2026-11-08 : stack PHP 8.4 + Symfony 7.4 LTS (installé via Symfony CLI, pas Composer) + MariaDB 11.4.13 + Redis 8 (o2switch) ; Symfony LSP standalone branché comme LSP à OpenCode ; dashboard protégé par TOTP + rate limiter + reset password ; GitHub Actions (security check Symfony CLI hebdo, health prod quotidien 3h00) ; env local = `symfony server` + Docker compose (mariadb, redis, devmailer) ; déploiement UNIQUEMENT par SSH depuis la machine du propriétaire via un CLI Go (push git vers repo bare o2switch + hook post-receive), pas de rsync, pas de clone GitHub depuis o2switch, pas de cron o2switch ; AUCUN import WordPress ; Doctrine Migrations ordonnées par année/mois ; admin hybride EasyAdminBundle (CRUD) + custom Symfony UX (éditeur/preview) ; site multi-pages (inspi emonin.dev) ; assets JS uniquement Stimulus + TypeScript ; le site doit fonctionner sans Redis ; architecture DDD event-driven (pas de CQRS systématique), SOLID/KISS/DRY, patterns Constant et Décorateur — le tout consigné dans `aidd_docs/memory/` (banque projet) et `AGENTS.md`.

## Phases

| #   | Phase                                            | File                         | Depends on |
| --- | ------------------------------------------------ | ---------------------------- | ---------- |
| 1   | Socle via Symfony CLI + env local Docker + CI    | [`phase-1.md`](./phase-1.md) | none       |
| 2   | Modèle Doctrine (migrations YYYY\MM) + seed      | [`phase-2.md`](./phase-2.md) | 1          |
| 3   | Sécurité (TOTP, rate limiter, reset) + EasyAdmin | [`phase-3.md`](./phase-3.md) | 1          |
| 4   | Front public multi-pages + Stimulus/TS + Turbo   | [`phase-4.md`](./phase-4.md) | 2          |
| 5   | Dashboard : pages (custom) + médias (EasyAdmin)  | [`phase-5.md`](./phase-5.md) | 2, 3       |
| 6   | Éditeur de blocs + autosave du brouillon         | [`phase-6.md`](./phase-6.md) | 5          |
| 7   | Preview live + versioning + cache Redis optionnel | [`phase-7.md`](./phase-7.md) | 4, 6       |
| 8   | Déploiement CLI Go/SSH + publication + prod      | [`phase-8.md`](./phase-8.md) | 4, 6, 7    |

## External sources

| URL | What it settled |
| --- | --------------- |
| https://ngandu.dev/fr/blog/symfony-clean-architecture-ddd | Clean architecture + DDD en Symfony (structure Domain/Application/Infrastructure/Presentation) |
| https://openclassrooms.com (parcours design patterns) | Design patterns Constant et Décorateur retenus |
| https://symfony.com/doc/current/setup.html | Installation via Symfony CLI (`symfony new`), `symfony server`, LSP standalone |
| https://symfony.com/doc/current/ux.html | Symfony UX (Turbo, Stimulus, Live Components), assets TS via esbuild + AssetMapper |
| https://symfony.com/doc/current/doctrine.html | Doctrine ORM, migrations, champs JSON |
| https://symfony.com/doc/current/security.html + scheb/2fa | Sécurité : TOTP 2FA, rate limiter, reset password, rôles |
| https://github.com/EasyCorp/EasyAdminBundle | EasyAdminBundle comme socle CRUD admin |
| https://faq.o2switch.fr/ | o2switch : PHP natif, MariaDB/Redis natifs, git fourni, pas de DNS GitHub autorisés, déploiement SSH |
| https://docs.github.com/actions | GitHub Actions : CI push, cron hebdo (security), cron quotidien 3h00 (health) |

## Impact

| Category | Verdict and evidence | Handled in |
| -------- | -------------------- | ---------- |
| runtime | o2switch mutualisé : PHP natif toujours chaud, MariaDB et Redis fournis nativement, git fourni mais sans accès DNS à GitHub — observé via la FAQ o2switch | 1, 8 |
| regression | none — projet greenfield, la preprod WordPress est remplacée sans import de contenu (quasi vide) | none |
| architecture | DDD event-driven (Domain/Application/Infrastructure/Presentation), SOLID/KISS/DRY, patterns Constant + Décorateur ; dashboard privé (admin.…) + site public multi-pages servi par Symfony, cache full-page Redis en décorateur avec fallback BDD ; un seul dépôt git public MIT ; contenu en MariaDB versionné par snapshots — consigné dans `aidd_docs/memory/` | 1-8 |
| documentation | README/INSTALL + `aidd_docs/memory/` (banque projet) + AGENTS.md câblé pour OpenCode | 1 |

## Rules

| Rule | Why it applies |
| ---- | -------------- |
| Règles d'architecture dans `aidd_docs/memory/` (banque projet) | Décision verrouillée : DDD event-driven, SOLID, KISS, DRY, patterns Constant et Décorateur |
| Un seul dépôt git public MIT | Décision verrouillée ; le contenu vit en BDD, pas de submodule |
| Installation via Symfony CLI, jamais `composer create-project` | Décision verrouillée (Symfony 7.4 LTS + LSP standalone pour OpenCode) |
| Le site public fonctionne sans Redis | Le cache Redis est un décorateur optionnel avec fallback BDD (pattern Décorateur) |
| Pas de génération statique : le public est servi par Symfony | PHP natif toujours chaud sur o2switch ; publication instantanée |
| Cache full-page Redis côté public, invalidé par événement à la publication | Perf au régime ; le public ne voit jamais un contenu périmé |
| Preview = même moteur Twig que le public | La preview est exactement le rendu public (draft vs published) |
| Assets JS uniquement Stimulus + TypeScript (esbuild) | Décision verrouillée |
| Migrations Doctrine ordonnées par année et mois | `organize_migrations: BY_YEAR_AND_MONTH` |
| Déploiement par SSH depuis la machine du propriétaire uniquement, via le CLI Go | o2switch ne peut pas joindre GitHub ; pas de rsync, pas de cron o2switch |
| Pas d'import WordPress | Le contenu en ligne est quasi vide, saisi à la main |
| Le site est multi-pages (inspi emonin.dev), pas une one-page | Décision verrouillée |
| Dashboard : TOTP 2FA + rate limiter + reset password | Sécurité en vigueur, voir `auth.md` |
| Échanges et livrables en français | Règle de la session |

## Decisions

| Decision | Why |
| -------- | --- |
| Stack : PHP 8.4, Symfony 7.4 LTS via Symfony CLI, MariaDB 11.4, Redis 8, o2switch | Versions fixées par l'utilisateur ; PHP natif sur l'hébergement |
| Architecture DDD event-driven, pas de CQRS systématique | Restitution du contenu via Redis en read model, avec dégradation gracieuse si Redis tombe |
| Patterns Constant + Décorateur imposés | Noms d'événements/tags constants ; cache Redis = décorateur du dépôt BDD avec fallback |
| Admin hybride : EasyAdminBundle (CRUD users/médias) + custom Symfony UX (éditeur de blocs, preview) | Aller vite sur le standard (docs riches : login, reset password) tout en gardant l'UX sur mesure là où c'est nécessaire |
| Déploiement : CLI Go local → push SSH vers repo bare o2switch → hook post-receive | Seule voie possible (pas d'accès GitHub depuis o2switch), 100 % git, pas de rsync |
| Pas de cron o2switch ; Redis géré par l'hébergeur | Le site fonctionne sans Redis ; la chute de Redis n'est pas dramatique |
| Site multi-pages | Le portfolio peut évoluer comme emonin.dev |
| Publication = flip draft→published + événement + invalidation des tags Redis | Instantané, traçable, réversible |
