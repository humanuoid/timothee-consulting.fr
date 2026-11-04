---
objective: "Livrer un CMS Symfony maison (dashboard sécurisé avec brouillons, preview live et publication instantanée) qui sert le portfolio de Timothée Consulting en dynamique Symfony, avec cache full-page Redis et navigation Turbo côté public, déployé sur o2switch."
status: pending
---

# Plan: CMS Symfony + site public dynamique caché (Redis) pour le portfolio Timothée Consulting

Source: Conversation OpenChamber du 2026-11-04 (session-25e5d2d9). Décisions verrouillées avec l'utilisateur : Symfony + Symfony UX + Twig, un seul dépôt git, contenu en MariaDB avec versioning par snapshots, Redis comme cache (admin + full-page côté public), PAS de génération statique (révision du 2026-11-04 : le site public est servi en dynamique par Symfony avec navigation Turbo et cache invalidé à la publication), hébergement o2switch (PHP natif, MariaDB via cPanel, Redis disponible).

## Phases

| #   | Phase                                            | File                         | Depends on |
| --- | ------------------------------------------------ | ---------------------------- | ---------- |
| 1   | Socle Symfony + environnement o2switch           | [`phase-1.md`](./phase-1.md) | none       |
| 2   | Modèle de données + import du contenu WordPress  | [`phase-2.md`](./phase-2.md) | 1          |
| 3   | Sécurité + layout admin                          | [`phase-3.md`](./phase-3.md) | 1          |
| 4   | Front public dynamique + Turbo + contact         | [`phase-4.md`](./phase-4.md) | 2          |
| 5   | Dashboard : liste des pages + médias             | [`phase-5.md`](./phase-5.md) | 2, 3       |
| 6   | Éditeur de blocs + autosave du brouillon         | [`phase-6.md`](./phase-6.md) | 5          |
| 7   | Preview live + versioning + cache Redis          | [`phase-7.md`](./phase-7.md) | 4, 6       |
| 8   | Mise en prod : publication instantanée + cache   | [`phase-8.md`](./phase-8.md) | 4, 6, 7    |

## External sources

| URL | What it settled |
| --- | --------------- |
| https://symfony.com/doc/current/index.html | Stack Symfony 7 LTS et structure de projet retenue |
| https://symfony.com/doc/current/ux.html | Symfony UX (Turbo, Stimulus, Live Components) pour le dashboard réactif sans SPA lourde |
| https://turbo.hotwired.dev/handbook/introduction | Turbo Drive côté public : navigation sans rechargement, servi via AssetMapper |
| https://symfony.com/doc/current/doctrine.html | Modèle Doctrine, migrations, champs JSON pour les blocs et snapshots |
| https://faq.o2switch.fr/ | o2switch : PHP natif toujours chaud, MariaDB via cPanel, Redis disponible, déploiement git/SSH, cron |
| https://redis.io/docs/latest/ | Redis comme cache applicatif (admin) et full-page (public), invalidation par tags |

## Impact

| Category | Verdict and evidence | Handled in |
| -------- | -------------------- | ---------- |
| runtime | o2switch mutualisé : PHP natif (toujours chaud, pas de cold start), MariaDB et Redis fournis nativement — observé via la FAQ o2switch | 1, 8 |
| regression | none — projet greenfield, aucun code existant modifié ; la preprod WordPress est remplacée, pas patchée | none |
| architecture | Dashboard Symfony privé (admin.…) + site public servi en dynamique par Symfony sur le domaine principal, mis en cache full-page dans Redis et invalidé à chaque publication ; navigation Turbo côté public ; un seul dépôt git ; contenu en MariaDB versionné par snapshots | 1-8 |
| documentation | README + procédure de déploiement o2switch à rédiger (création BDD, .env, SSL, cron) | 1, 8 |

## Rules

| Rule | Why it applies |
| ---- | -------------- |
| Un seul dépôt git pour tout le projet | Décision verrouillée : le contenu vit en BDD, plus de submodule |
| Pas de génération statique : le public est servi par Symfony | Sur o2switch PHP est natif et toujours chaud ; publication instantanée sans build |
| Cache full-page Redis côté public, invalidé à chaque publication par tags | Perf proches du statique au régime ; le public ne voit jamais un contenu périmé |
| Navigation côté public via Turbo Drive | Sensation de SPA sans écrire de JS, en s'appuyant sur les bundles Symfony (AssetMapper) |
| La preview utilise le même moteur Twig que le public | La preview est exactement le rendu public (draft vs published) |
| Toute sauvegarde crée un snapshot de version | Compense l'absence de versioning git du contenu (passage en BDD) |
| Routes publiques en GET seul ; formulaire de contact protégé (CSRF, rate limiter, honeypot) | Le public exécute du PHP : surface d'attaque minimale |
| Échanges et livrables en français | Règle de la session (AGENTS.md) |

## Decisions

| Decision | Why |
| -------- | --- |
| Symfony + Symfony UX + Twig comme stack | PHP natif sur o2switch, sécurité mature, UX moderne sans SPA React lourde |
| MariaDB comme stockage unique du contenu | Disponible nativement sur o2switch ; requêtes et transactions ; versioning par table content_version |
| Pas de génération statique (révision) : rendu dynamique + cache Redis + Turbo public | PHP toujours chaud sur o2switch, publication instantanée, plus de build ni de cron de génération ; Turbo couvre la fluidité perçue |
| Redis = cache admin (preview, requêtes) + cache full-page public | Un seul serveur Redis o2switch couvre les deux besoins |
| Publication = flip draft→published + snapshot + invalidation des tags Redis | Instantané, traçable, réversible (restore de version) |
