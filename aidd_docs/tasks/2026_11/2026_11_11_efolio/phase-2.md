---
status: pending
---

# Instruction: Modèle de données + mapping Infrastructure

## Architecture projection

```txt
.
├── ✏️ config/packages/doctrine.yaml
├── ✏️ config/packages/doctrine_migrations.yaml
├── ✅ migrations/2026/11/Version20261112000001.php
├── ✅ src/Domain/Entity/User.php
├── ✅ src/Domain/Entity/Media.php
├── ✅ src/Domain/Entity/Page.php
├── ✅ src/Domain/Entity/PageTranslation.php
├── ✅ src/Domain/Entity/PageModel.php
├── ✅ src/Domain/Repository/UserRepositoryInterface.php
├── ✅ src/Domain/Repository/MediaRepositoryInterface.php
├── ✅ src/Domain/Repository/PageRepositoryInterface.php
├── ✅ src/Domain/Repository/PageModelRepositoryInterface.php
├── ✅ src/Infrastructure/Doctrine/Mapping/
├── ✅ src/Infrastructure/Repository/UserRepository.php
├── ✅ src/Infrastructure/Repository/MediaRepository.php
├── ✅ src/Infrastructure/Repository/PageRepository.php
├── ✅ src/Infrastructure/Repository/PageModelRepository.php
└── ✅ src/DataFixtures/AppFixtures.php
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| -------- | ------- | ------------------- | ----- |
| Setup | Base vide (conteneur MariaDB local), phase 1 OK | 0 table métier | cli |
| Happy path | `doctrine:migrations:migrate` puis `doctrine:fixtures:load` | Tables créées, 2 users, 1 modèle 6 slots, 1 page FR en brouillon | cli |
| Edge case | Recharger les fixtures | Aucune duplication | cli |
| Teardown | `doctrine:database:drop --force --if-exists` | Base vide | cli |

## Tasks to do

### `1)` Entités Domain pures

> PHP nu : aucun attribut ORM, aucun import Doctrine — le mapping vit dans l'Infrastructure.

1. `User` : email unique, hash, roles, fullName, locale, totpSecret nullable, recoveryCodes hashés nullable, mustChangePassword
2. `Media` : fileName, altText obligatoire, path, mimeType, size, createdAt
3. `Page` : modelRef (PageModel), createdBy/updatedBy (User), createdAt/updatedAt — pas d'arbre en V1
4. `PageTranslation` : page, locale (fr ou en), slug, title, metaTitle, metaDescription, draft (JSON), published (JSON nullable), status (draft ou published), publishedAt
5. `PageModel` : name, key unique, slots (JSON ordonné de clés de composants)

### `2)` Mapping Doctrine en YAML, côté Infrastructure

> Clean architecture : le mapping est un détail d'infrastructure — jamais dans le Domain, jamais dans config/.

1. Un fichier de mapping YAML par entité dans `src/Infrastructure/Doctrine/Mapping/`
2. `config/packages/doctrine.yaml` : mapping `type: yaml`, `dir` vers `src/Infrastructure/Doctrine/Mapping`, `prefix: App\Domain`
3. `doctrine:schema:validate` OK ; les entités restent sans aucun import

### `3)` Ports + implémentations + alias par attribut

> Zéro déclaration dans config/services.yaml : chaque implémentation porte son `#[AsAlias]`.

1. 4 interfaces dans `src/Domain/Repository`, 4 classes Doctrine dans `src/Infrastructure/Repository`
2. `#[AsAlias]` sur chaque repository (une seule interface implémentée → l'interface devient l'alias d'autowiring) ; `config/services.yaml` inchangé
3. `lint:container` vert

### `4)` Migration YYYY/MM

> Convention verrouillée : ordonnée par année et mois.

1. `organize_migrations: BY_YEAR_AND_MONTH` vérifié dans `config/packages/doctrine_migrations.yaml`
2. Première migration sous `migrations/2026/11/`
3. Unicité (locale, slug) et index sur (page_id, locale)

### `5)` Fixtures idempotentes

> Rechargeables sans doublon ; squelette du contenu spec §3.

1. admin + editor (`mustChangePassword=true`, rôles)
2. PageModel « one-page » : slots [header, hero, parcours, competences, projets, contact]
3. Page « accueil » + PageTranslation FR en draft

## Test acceptance criteria

| Task | Acceptance criteria |
| ---- | ------------------- |
| 1 | `doctrine:schema:validate` OK ; aucune entité Domain n'importe Symfony ou Doctrine (arch tests phase 1) |
| 2 | Le mapping vit en YAML dans `src/Infrastructure/Doctrine/Mapping/` ; rien dans `config/` ni dans les entités |
| 3 | `lint:container` compile ; chaque port se résout via son `#[AsAlias]` ; `config/services.yaml` inchangé |
| 4 | Migration sous `migrations/2026/11/` ; contrainte (locale, slug) unique effective |
| 5 | Recharger les fixtures ne crée aucun doublon |
