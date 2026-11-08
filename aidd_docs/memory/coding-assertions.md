# Coding Assertions

Règles absolues (détails et exemples dans `architecture.md`) : DDD event-driven, SOLID, KISS, DRY, pas de sur-ingénierie, patterns Constant et Décorateur imposés.

## Règles de code

- **Pas de logique métier dans les contrôleurs** : HTTP/CLI in → Application out. Un contrôleur qui calcule ou décide métier est à refactorer.
- **Toujours injecter les interfaces** (ports du Domain), jamais une implémentation concrète.
- **Pas de chaînes magiques** : événements, tags de cache, slugs → constantes (`EventNames`, `CacheTags`).
- **Un service = une responsabilité** ; si un service grossit, le découper, pas l'alourdir.
- **Le brouillon n'est jamais mis en cache** ; le public ne lit que `published`.
- **Jamais de rendu dupliqué** : un seul moteur Twig (public = preview = admin).
- **Vérifier toute API/signature contre la doc officielle** avant de l'utiliser.
- **Tests avant commit** : tout nouveau comportement a un test (unitaire pour Application/Domain, fonctionnel pour Presentation).

## Before commit

| Order | Command | Checks |
| ----- | ------- | ------ |
| 1 | `php bin/console lint:yaml config/` | configuration valide |
| 2 | `php bin/console lint:twig templates/` | templates valides |
| 3 | `php bin/console lint:container` | conteneur compilable |

## Before push

| Order | Command | Checks |
| ----- | ------- | ------ |
| 1 | `vendor/bin/phpunit` | suite de tests verte |
| 2 | `symfony security:check` | aucune vulnérabilité connue |
| 3 | `node scripts/build-assets.mjs` | assets TypeScript compilés (esbuild) |

## Behavior

- Une règle violée (typage, test, pattern) = un agent dédié par catégorie corrige avant le commit.
