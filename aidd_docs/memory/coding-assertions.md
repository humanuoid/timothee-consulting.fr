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
- **Documentation du code : anglais simple, WHY jamais WHAT, et pas systématique.** On n'explique jamais ce que le code fait (le code le dit déjà), mais pourquoi il le fait — pour un lecteur français qui lit peu l'anglais. Une évidence n'a pas besoin de commentaire.
  - ❌ `// renders the page as HTML` (WHAT — inutile)
  - ✅ `// preview bypasses the cache: the editor must see the draft, not the published page` (WHY)

## Outillage qualité

- **MagoPHP** pour le reste : `mago lint` (lint) et `mago fmt` (format) — l'exécuter avant chaque commit.
- **PHPStan** pour les conventions PSR : `phpstan analyse` (niveau max + règles PSR).
- **Pest** pour les tests (voir `testing.md`).

## Before commit

| Order | Command | Checks |
| ----- | ------- | ------ |
| 1 | `mago lint` | lint PHP |
| 2 | `mago fmt --check` | formatage |
| 3 | `php bin/console lint:yaml config/` | configuration valide |
| 4 | `php bin/console lint:twig templates/` | templates valides |
| 5 | `php bin/console lint:container` | conteneur compilable |

## Before push

| Order | Command | Checks |
| ----- | ------- | ------ |
| 1 | `vendor/bin/pest` | tests unit + arch verts |
| 2 | `phpstan analyse` | conventions PSR respectées |
| 3 | `symfony security:check` | aucune vulnérabilité connue |
| 4 | `node scripts/build-assets.mjs` | assets TypeScript compilés (esbuild) |

## Behavior

- Une règle violée (typage, test, pattern) = un agent dédié par catégorie corrige avant le commit.
