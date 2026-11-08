# Coding Assertions

Règles absolues (voir aussi `architecture.md`) : SOLID, KISS, DRY, pas de sur-ingénierie, DDD event-driven, design patterns Constant et Décorateur.

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
- Toujours vérifier une API/signature contre la doc officielle avant de l'utiliser.
