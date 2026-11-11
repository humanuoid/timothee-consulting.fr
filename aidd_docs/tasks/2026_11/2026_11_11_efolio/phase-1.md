---
status: pending
---

# Instruction: Outillage qualité + CI

## Architecture projection

```txt
.
├── ✏️ composer.json
├── ✏️ phpunit.dist.xml
├── ✅ phpstan.neon
├── ✅ tests/Pest.php
├── ✅ tests/Arch/ArchitectureTest.php
└── ✏️ .github/workflows/ci.yaml
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| -------- | ------- | ------------------- | ----- |
| Setup | `composer install` propre, tests/ quasi vide | Pest installable | cli |
| Happy path | `vendor/bin/pest` puis push | Arch + suite vertes, CI verte | cli, system |
| Edge case | Ajouter temporairement `use Doctrine\ORM\Mapping as ORM` dans une classe Domain | `vendor/bin/pest --group=arch` rouge | cli |
| Teardown | Retirer le contre-exemple | Suite verte à nouveau | cli |

## Tasks to do

### `1)` Pest remplace PHPUnit

> testing.md impose Pest, la CI lance phpunit sur des tests vides.

1. `composer require --dev pestphp/pest`, retirer `phpunit/phpunit` des dépendances directes
2. `tests/Pest.php` : liaison avec `tests/bootstrap.php` existant, groupes `arch`, `unit`, `feature`
3. `phpunit.dist.xml` adapté aux groupes Pest

### `2)` Tests d'architecture

> Une règle par test, toutes les couches.

1. `tests/Arch/ArchitectureTest.php` : Domain n'importe ni Symfony, ni Doctrine, ni Infrastructure — donc aucun attribut ORM possible sur les entités
2. Application ne dépend que de Domain ; Presentation → Application ; Infrastructure implémente les ports Domain
3. Contre-exemple vérifié une fois puis retiré

### `3)` PHPStan niveau max

> Conventions PSR (coding-assertions.md).

1. `composer require --dev phpstan/phpstan` + jeu de règles PSR
2. `phpstan.neon` : level max, `src/` analysé
3. `vendor/bin/phpstan analyse` vert sur l'existant

### `4)` Mago lint + format

> Outil qualité du projet, absent de la CI actuelle.

1. mago installé en PHAR épinglé (local `~/.local/bin`, téléchargement épinglé en CI)
2. `mago lint` et `mago fmt --check` verts sur l'existant

### `5)` CI corrigée

> Le step `vendor/bin/phpunit` devient Pest ; mago, phpstan et security:check rejoignent le push.

1. Remplacer le step tests par `vendor/bin/pest`
2. Ajouter `mago lint`, `mago fmt --check`, `vendor/bin/phpstan analyse`
3. Ajouter `symfony security:check` sur chaque push (en plus du cron hebdo existant)

## Test acceptance criteria

| Task | Acceptance criteria |
| ---- | ------------------- |
| 1 | `vendor/bin/pest` vert en local et en CI |
| 2 | Un `use Doctrine\...` ajouté dans `src/Domain` fait échouer `vendor/bin/pest --group=arch` |
| 3 | `vendor/bin/phpstan analyse` : 0 erreur |
| 4 | `mago lint` et `mago fmt --check` propres |
| 5 | La CI exécute pest + mago + phpstan + lint yaml/twig/container + typescript:build + security:check et passe |
