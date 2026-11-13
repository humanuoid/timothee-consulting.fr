---
status: done
---

# Instruction: Modèle de données + mapping Infrastructure

## Architecture projection

```txt
.
├── ✏️ composer.json
├── ✏️ config/bundles.php
├── ✏️ config/packages/doctrine.yaml
├── ✅ migrations/2026/11/Version20261113*.php
├── ✅ src/Domain/I18n/Locales.php
├── ✅ src/Domain/Entity/User.php
├── ✅ src/Domain/Entity/Media.php
├── ✅ src/Domain/Entity/Page.php
├── ✅ src/Domain/Entity/PageContent.php
├── ✅ src/Domain/Entity/PageModel.php
├── ✅ src/Domain/Repository/UserRepositoryInterface.php
├── ✅ src/Domain/Repository/MediaRepositoryInterface.php
├── ✅ src/Domain/Repository/PageRepositoryInterface.php
├── ✅ src/Domain/Repository/PageModelRepositoryInterface.php
├── ✅ src/Infrastructure/Doctrine/Mapping/Entity.User.orm.xml
├── ✅ src/Infrastructure/Doctrine/Mapping/Entity.Media.orm.xml
├── ✅ src/Infrastructure/Doctrine/Mapping/Entity.Page.orm.xml
├── ✅ src/Infrastructure/Doctrine/Mapping/Entity.PageContent.orm.xml
├── ✅ src/Infrastructure/Doctrine/Mapping/Entity.PageModel.orm.xml
├── ✅ src/Infrastructure/Repository/UserRepository.php
├── ✅ src/Infrastructure/Repository/MediaRepository.php
├── ✅ src/Infrastructure/Repository/PageRepository.php
├── ✅ src/Infrastructure/Repository/PageModelRepository.php
├── ✅ src/DataFixtures/AppFixtures.php
├── ✅ src/DataFixtures/PlainPasswordUser.php
├── ✅ tests/Unit/Entity/MediaTest.php
├── ✅ tests/Unit/Entity/PageContentTest.php
└── ✏️ aidd_docs/memory/database.md
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| -------- | ------- | ------------------- | ----- |
| Setup | `docker compose up -d database`, base vide | 0 table métier | cli |
| Happy path | `symfony console doctrine:migrations:migrate` puis `doctrine:fixtures:load` | 5 tables (`user`, `media`, `page_model`, `page`, `page_content`) avec FK, uniques, utf8mb4 ; 2 users, 1 modèle 6 slots, 1 page + contenu FR en draft | cli |
| Edge case | Recharger `doctrine:fixtures:load` (purge par défaut, jamais `--append`) | Compteurs identiques, aucun doublon | cli |
| Edge case | `vendor/bin/pest`, `vendor/bin/phpstan analyse`, `mago lint`, `mago fmt --check` | Tout vert ; `--group=arch` : Domain reste sans Doctrine/Symfony | cli |
| Teardown | `symfony console doctrine:database:drop --force --if-exists` | Base vide | cli |

## Tasks to do

### `1)` Entités Domain pures + constantes Locales

> PHP nu : zéro attribut ORM, zéro import Doctrine/Symfony — le mapping vit en Infrastructure. `final` + `strict_types` (règles d'arch phase 1), constructeurs privés + `::create()`.

1. `Locales` (pattern Constant) : `FR = 'fr'`, `EN = 'en'`, `ALL = ['fr', 'en']`
2. `User` : email, password (hash), roles (array), fullName, locale, totpSecret ?string, recoveryCodes ?array, mustChangePassword bool, createdAt/updatedAt — n'implémente aucune interface Symfony (l'adapter SecurityUser arrive en phase 3)
3. `Media` : fileName, altText, path, mimeType, size, createdAt ; `::create()` refuse altText vide
4. `Page` : model (PageModel), createdBy (User), updatedBy ?User, createdAt/updatedAt, contents (côté inverse)
5. `PageContent` : page, locale (validée contre `Locales`), slug, title, metaTitle, metaDescription, draft (array), published ?array, publishedAt ?DateTimeImmutable ; **pas de colonne status** : `isPublished()` = `published !== null` ; `publish()` copie draft → published + publishedAt (la sémantique de publication de la phase 6, posée dès le modèle)
6. `PageModel` : name, key (colonne `model_key` — `key` est un mot réservé SQL), slots (array ordonné de clés de composants)
7. `touch()` sur Page/User/PageContent/PageModel pour updatedAt ; PHPStan level max : phpdoc types sur tous les arrays (`array<string, mixed>`, etc.)

### `2)` Mapping XML en Infrastructure

> Même intention que la règle « YAML » du plan initial (mapping hors Domain, hors config/) ; le YAML est impossible sur cette stack : ORM 3.x a retiré son driver YAML et DoctrineBundle n'accepte que `xml|php|staticphp|attribute` (DoctrineExtension.php:333, vérifié dans le vendor).

1. `config/packages/doctrine.yaml` : bloc `mappings.Domain` → `type: xml`, `dir: '%kernel.project_dir%/src/Infrastructure/Doctrine/Mapping'`, `prefix: 'App\Domain'` ; `validate_xml_mapping: true` déjà en place → XSD actif
2. 5 fichiers plats `Entity.<Classe>.orm.xml` (naming SimplifiedXmlDriver vérifié : reste du FQCN après le préfixe, antislashs en points). Convention XSD ORM 3 vérifiée : `<field name="...">` mais relations en `<many-to-one field="...">` ; uniques mono-colonne via attribut `unique="true"`, composites via `<unique-constraints>`
3. Types : `json` (roles, recoveryCodes, slots, draft, published), `datetime_immutable` ; FK contents `on-delete="CASCADE"`, updatedBy `SET NULL`, createdBy `RESTRICT` (on ne supprime pas l'auteur d'une page)
4. Uniques : `user.email`, `media.path`, `page_model.model_key`, `page_content(locale, slug)` **et** `(page_id, locale)` — un contenu par langue et par page
5. `doctrine:mapping:info` liste les 5 entités ; `doctrine:schema:validate` vert ; `grep -r 'Doctrine\|Symfony' src/Domain/` vide

### `3)` Ports + repositories + #[AsAlias] explicites

> Zéro déclaration dans config/services.yaml ; l'attribut vit dans la couche qui possède la dépendance.

1. 4 ports minces dans `src/Domain/Repository` : un `save()` + un finder chacun (`findByEmail`, `find`, `find`, `findByKey`) — le reste pousse avec les phases 5-7
2. 4 repositories `final` dans `src/Infrastructure/Repository`, `extends ServiceEntityRepository` (ManagerRegistry + classe entière en constructeur), phpdoc `@extends ServiceEntityRepository<X>`
3. `#[AsAlias(XRepositoryInterface::class)]` avec l'interface **en dur** : hériter de ServiceEntityRepository ajoute des interfaces, la forme sans argument de l'attribut échouerait
4. `lint:container` + `debug:autowiring` : chaque port résout son implémentation ; `config/services.yaml` inchangé

### `4)` Migration 2026/11

> `organize_migrations: BY_YEAR_AND_MONTH` déjà en place dans doctrine_migrations.yaml (vérifié).

1. `symfony console doctrine:migrations:diff` → génère sous `migrations/2026/11/` (nom réel au timestamp, jamais codé en dur)
2. Revoir le diff : FK, 2 uniques composés, utf8mb4 ; puis `doctrine:migrations:migrate --no-interaction`
3. Unicité effective : INSERT SQL dupliqué `(locale, slug)` puis `(page_id, locale)` rejeté

### `5)` Fixtures idempotentes

> Purge par défaut = rechargement sans doublon ; le bundle fixtures n'est pas encore installé (absent de composer.json, vérifié).

1. `composer require --dev orm-fixtures` (résout `doctrine/doctrine-fixtures-bundle ^4.3`, dry-run vérifié) — la recipe inscrit le bundle
2. `AppFixtures` (final, ordre : users → modèle → page → traduction FR) : admin `ROLE_ADMIN` + editor `ROLE_EDITOR`, `mustChangePassword = true`
3. `PlainPasswordUser` : mini-adapter `PasswordAuthenticatedUserInterface` local aux fixtures → hasher via `UserPasswordHasherInterface` sans faire implémenter Symfony au User Domain (signature `hashPassword($user, $plain)` vérifiée)
4. PageModel « one-page » : slots `[header, hero, parcours, competences, projets, contact]` ; page « accueil » + traduction FR avec `draft = []` (le payload composants est défini en phases 4/6 ; les fixtures s'enrichissent alors)
5. Jamais `--append` ; rechargement = purge + load

### `6)` Tests unitaires Domain + porte qualité

> testing.md : unitaires Domain en miroir de src/Domain, quand la logique n'est pas évidente.

1. `tests/Unit/Entity/MediaTest.php` : altText vide → exception
2. `tests/Unit/Entity/PageContentTest.php` : locale hors `Locales` → exception ; `publish()` copie draft et pose publishedAt ; `isPublished()` faux puis vrai
3. Porte avant commit : `vendor/bin/pest`, `vendor/bin/phpstan analyse`, `mago lint`, `mago fmt --check`, lints yaml/twig/container

### `7)` Mémoire projet

> database.md décrit l'ancien modèle (Page/Block/ContentVersion) ; le schéma change de nature.

1. `aidd_docs/memory/database.md` : 5 tables, draft/published JSON par traduction, mapping XML en Infrastructure, plus de Block ni ContentVersion

## Test acceptance criteria

| Task | Acceptance criteria |
| ---- | ------------------- |
| 1 | `vendor/bin/pest --group=arch` vert : aucun `use Doctrine|Symfony` dans src/Domain, classes final, strict_types |
| 2 | `doctrine:schema:validate` vert ; 5 `.orm.xml` dans src/Infrastructure/Doctrine/Mapping, aucun mapping dans config/ ni dans les entités |
| 3 | `lint:container` compile ; `debug:autowiring` montre chaque port ; config/services.yaml inchangé |
| 4 | Migration sous migrations/2026/11/ ; INSERT dupliqué (locale, slug) et (page_id, locale) rejeté |
| 5 | `doctrine:fixtures:load` → 2 users, 1 modèle 6 slots, 1 page, 1 trad FR draft ; rechargement → mêmes compteurs |
| 6 | `vendor/bin/pest` vert ; phpstan 0 erreur ; mago lint/fmt propres |
| 7 | database.md reflète le nouveau schéma, sans Block/ContentVersion |
