---
status: in-progress
---

# Instruction: Outillage qualité + CI (+ refactor health)

## Architecture projection

```txt
.
├── ✏️ composer.json
├── ✏️ phpunit.dist.xml
├── ✅ phpstan.neon
├── ✅ mago.toml
├── ✅ tests/Pest.php
├── ✅ tests/Arch/ArchitectureTest.php
├── ✅ tests/Unit/.gitkeep
├── ✅ tests/Feature/.gitkeep
├── ✅ src/Domain/Health/HealthProbeInterface.php
├── ✅ src/Infrastructure/Health/DbalRedisHealthProbe.php
├── ✏️ src/Application/Health/HealthChecker.php
├── ✏️ src/Infrastructure/Kernel.php
├── ✏️ src/Presentation/Controller/HealthController.php
├── ✏️ src/Presentation/Command/HealthCommand.php
├── ✏️ .github/workflows/ci.yaml
├── ✏️ aidd_docs/memory/installation.md
├── ✏️ aidd_docs/memory/coding-assertions.md
└── ✏️ aidd_docs/memory/deployment.md
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| -------- | ------- | ------------------- | ----- |
| Setup | `composer install`, installer mago 1.59.0 | pest, mago, phpstan exécutables depuis la racine | cli |
| Happy path | `vendor/bin/pest`, `mago lint`, `mago fmt --check`, `vendor/bin/phpstan analyse`, puis push | tout vert en local, job CI verte (pest + mago + phpstan + lints + lsp + typescript + composer audit) | cli, system |
| Edge case | créer `src/Domain/ArchProbe.php` avec `use Doctrine\ORM\Mapping as ORM;` + `#[ORM\Entity]` | `vendor/bin/pest --group=arch` rouge, la règle « domain ne dépend de rien » nommée en échec | cli |
| Edge case | `curl -sk https://localhost/health` et `symfony console app:health` après le refactor | JSON identique (php, bdd, redis, time), 200 avec redis down | cli |
| Teardown | supprimer `ArchProbe.php` | suite verte à nouveau | cli |

## Tasks to do

### `1)` Pest remplace PHPUnit

> testing.md impose Pest ; la CI lance phpunit sur des tests vides.

1. `composer remove --dev phpunit/phpunit`, puis `composer require --dev pestphp/pest --with-all-dependencies` (Pest v4.7 : v5 exige symfony/process ^8.1, incompatible avec le lock Symfony 7.4 LTS ; phpunit ^12 revient en transitif ; plugin-arch exigé par pest core, pas de require explicite — vérifié)
2. composer.json : contrainte `php` `>=8.2` → `>=8.4` (Pest v5 exige ^8.4 ; local, CI et prod sont tous en 8.4) ; bloc `replace` resynchronisé sur le skeleton officiel ≥ 8.4 : ajouter `symfony/polyfill-php83` et `symfony/polyfill-php84` (no-ops sur 8.4, actuellement installés en réel dans vendor/ — symfony/clock, http-client, monolog-bundle, serializer les exigent) ; php72→php82, ctype, iconv restent (toujours déclarés par les composants 7.4) ; php85/php86, mbstring, intl-* ne se remplacent pas (fonctionnellement actifs sur 8.4)
3. `tests/Pest.php` : groupes par dossier — `pest()->group('arch')->in('Arch')`, `->group('unit')->in('Unit')`, `->group('feature')->in('Feature')` ; poser `tests/Unit/.gitkeep` et `tests/Feature/.gitkeep`
4. Convention verrouillée : `tests/Unit/` = miroir de `src/Domain` uniquement (tests unitaires Domain seulement), `tests/Feature/` = fonctionnels Presentation ; fichiers de test suffixés `Test.php` (découverte Pest ; vérifié : un fichier non suffixé n'est pas découvert)
5. `phpunit.dist.xml` : supprimer l'élément `<extensions>` vide et le `SHELL_VERBOSITY=-1` (mode quiet de Symfony Console : il muet totalement Pest/collision — vérifié) ; garder bootstrap, testsuites, source

### `2)` Tests d'architecture

> Une règle par règle d'architecture, toutes les couches, zéro exception.

1. `tests/Arch/ArchitectureTest.php` — matrice verrouillée en session (sémantique vérifiée : `toUse`/`toOnlyUse` matchent des préfixes multi-segments, un segment nu comme `Doctrine` ne matche rien ; les fonctions natives PHP ne comptent pas) :
   - `arch('domain ne dépend de rien')->expect('App\Domain')->toOnlyUse('App\Domain')`
   - `arch('application: domain et symfony de câblage seulement')->expect('App\Application')->toOnlyUse(['App\Application', 'App\Domain', 'Symfony\Component', 'Symfony\Contracts'])` (liste blanche : plus forte qu'une liste noire Doctrine, qui serait née incomplète)
   - `arch('presentation ne touche jamais l\'infrastructure')->expect('App\Presentation')->not->toUse('App\Infrastructure')`
   - `arch('infrastructure: ni application, ni presentation')->expect('App\Infrastructure')->not->toUse(['App\Application', 'App\Presentation'])`
   - `arch('strict types partout')->expect('App')->toUseStrictTypes()`
   - `arch('classes final')->expect('App')->classes()->toBeFinal()`
   - `arch()->preset()->php()` et `arch()->preset()->security()` (intl déjà requis via symfony/intl)
2. Si `toOnlyUse` compte les symboles natifs PHP (DateTimeImmutable…), tolérer le natif par le pattern documenté `pest()->beforeEach(fn () => $this->arch()->ignore([...]))` (pestphp.com/docs/arch-testing, section ignoring) — à vérifier au premier run
3. Contre-exemple : classe temporaire `src/Domain/ArchProbe.php` avec `use Doctrine\ORM\Mapping as ORM;` **et** `#[ORM\Entity]` appliqué (un import seul pourrait ne pas compter) → `vendor/bin/pest --group=arch` rouge → supprimer → vert

### `3)` Health conforme aux règles

> HealthChecker injecte DBAL en direct : viole la matrice et coding-assertions ; l'arch naît sans exception.

1. `src/Domain/Health/HealthProbeInterface.php` : port `probe(): array{bdd: string, redis: string}`
2. `src/Infrastructure/Health/DbalRedisHealthProbe.php` : implémente le port (ping DBAL + RESP PING sur TCP), `#[AsAlias(HealthProbeInterface::class)]`, `#[Autowire('%env(REDIS_URL)%')]`, `final` + `declare(strict_types=1)`
3. `src/Application/Health/HealthChecker.php` : n'injecte que `HealthProbeInterface` ; assemble php/time, expose `check()`/`isHealthy()` — contrat public inchangé
4. `src/Infrastructure/Kernel.php` : `declare(strict_types=1)` + `final class` ; idem strict_types sur les deux fichiers Presentation
5. Vérifier `/health` et `app:health` : JSON identique, 200 avec redis down

### `4)` PHPStan niveau max

> Rigueur de typage (coding-assertions) ; le style PSR-12, c'est mago (décision « split »).

1. `composer require --dev phpstan/phpstan phpstan/phpstan-strict-rules phpstan/phpstan-deprecation-rules`
2. `phpstan.neon` : `level: max`, `paths: [src]` (tests/ exclu en phase 1 ; l'extension PHPStan de Pest sera branchée quand tests/ sera analysé)
3. `vendor/bin/phpstan analyse` : 0 erreur — corriger les manques de types (parse_url peut retourner false, etc.)

### `5)` Mago lint + format

> Outil qualité du projet, absent de la CI ; preset formatter PSR-12.

1. Local : `curl --proto '=https' --tlsv1.2 -sSf https://carthage.software/mago.sh | bash -s -- --version=1.59.0`, binaire dans le PATH
2. `mago init` : preset PSR-12, intégration linter Symfony, paths `src/` + `tests/`, PHP 8.4 — `mago.toml` commité
3. `mago fmt` (applique PSR-12 sur l'existant), puis `mago lint` et `mago fmt --check` verts
4. `mago analyze` n'est pas retenu : l'analyse statique appartient à PHPStan

### `6)` CI corrigée

> Le step phpunit devient pest ; mago, phpstan et l'audit sécurité rejoignent chaque push ; une seule job.

1. Remplacer `vendor/bin/phpunit` par `vendor/bin/pest`
2. Ajouter `vendor/bin/phpstan analyse` après composer install
3. Ajouter l'install mago épinglée (même curl `--version=1.59.0`) et invoquer `./mago` directement — l'installeur dépose le binaire dans le répertoire courant, pas de GITHUB_PATH (résolu) — puis `mago lint` et `mago fmt --check`
4. Ajouter `composer audit` (recommandation CI de la doc Symfony : advisories PHP + GitHub Advisory Database, exit non-zero si vulnérabilité, sans CLI)
5. Conserver lsp:check, lint:yaml/twig/container, typescript:build ; security.yaml et health.yaml inchangés (health.yaml revu en phase 10)

### `7)` Docs mémoire

> Deux fichiers mémoire portent la commande sécurité erronée ; installation.md doit documenter mago.

1. `aidd_docs/memory/coding-assertions.md` : before-push `symfony security:check` → `symfony check:security`
2. `aidd_docs/memory/deployment.md` : « symfony security:check » hebdo → `symfony check:security`
3. `aidd_docs/memory/installation.md` : install mago épinglée dans les prérequis/outillage

## Test acceptance criteria

| Task | Acceptance criteria |
| ---- | ------------------- |
| 1 | `vendor/bin/pest` vert ; `--group=arch` ne lance que les arch ; `composer show symfony/polyfill-php83 symfony/polyfill-php84` ne liste plus rien (php85/86 restent) |
| 2 | contre-exemple Doctrine dans Domain → `--group=arch` rouge ; retiré → vert |
| 3 | `/health` et `app:health` retournent le même JSON ; `grep -r 'Doctrine' src/Application/` vide ; aucun `ignoring()` sur les règles de couche |
| 4 | `vendor/bin/phpstan analyse` : 0 erreur |
| 5 | `mago lint` et `mago fmt --check` propres ; `mago.toml` commité |
| 6 | CI verte sur push : pest + mago + phpstan + lints + lsp + typescript + composer audit |
| 7 | `grep -rn 'security:check' aidd_docs/memory/` ne retourne rien |
