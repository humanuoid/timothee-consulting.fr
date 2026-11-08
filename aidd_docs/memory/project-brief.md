# Project Brief

## What it is

- CMS maison en Symfony pour le portfolio de Timothée Consulting (`timothee-consulting.fr`) : un dashboard d'administration sécurisé permet d'éditer les pages, de prévisualiser avant publication et de publier instantanément. Le site public est servi par Symfony, mis en cache dans Redis, avec navigation Turbo.

## Why it exists

- Remplacer un WordPress de preprod jugé trop rigide et chronophage : un outil sur mesure, léger, piloté par l'IA (skills/agents OpenCode), déployé sur l'hébergement o2switch existant.

## Domain language

| Term | Meaning |
| ---- | ------- |
| Page | Une page du site public, identifiée par son slug. Le site est multi-pages, pas une one-page |
| Bloc | Un segment ordonné d'une page (hero, parcours, compétences, projets, contact, texte riche) |
| Brouillon (draft) | Copie de travail d'une page, éditable et prévisualisable, jamais servie au public |
| Publication | Passage du brouillon à la version publiée : instantané, avec snapshot et invalidation du cache |
| Version (snapshot) | Photo JSON du contenu à un instant, restaurable depuis l'historique |
| Aperçu (preview) | Rendu de la page avec le brouillon, via le même moteur Twig que le public |

## Key features

- Dashboard admin sécurisé (TOTP, rate limiter, rôles) : liste des pages, médias, historique
- Éditeur de blocs (pas de gros formulaire) avec autosave du brouillon
- Preview live identique au rendu public
- Historique de versions avec restauration
- Publication instantanée + invalidation du cache Redis
- Site public multi-pages servi par Symfony, cache full-page Redis en décorateur (le site fonctionne sans Redis), navigation Turbo
