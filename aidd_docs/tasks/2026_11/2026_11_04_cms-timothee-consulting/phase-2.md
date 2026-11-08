---
status: pending
---

# Instruction: Modèle Doctrine (migrations ordonnées YYYY\MM) + fixtures minimales, sans import WordPress

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ config/packages/doctrine_migrations.yaml
├── ✅ migrations/2026/11/Version20261108000000.php
├── ✅ src/Domain/Entity/User.php
├── ✅ src/Domain/Entity/Page.php
├── ✅ src/Domain/Entity/Block.php
├── ✅ src/Domain/Entity/ContentVersion.php
├── ✅ src/Domain/Entity/Media.php
├── ✅ src/Domain/Repository/PageRepositoryInterface.php
├── ✅ src/Infrastructure/Repository/PageRepository.php
├── ✅ src/Application/Service/DraftService.php
├── ✅ src/DataFixtures/AppFixtures.php
└── ✅ src/Command/SeedUsersCommand.php
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Base vide (conteneur mariadb local), phase 1 OK | 0 table métier | cli |
| Happy path | `php bin/console doctrine:migrations:migrate` puis `doctrine:fixtures:load` | Tables créées, 2 users, 1 page accueil en brouillon | cli |
| Edge case | Relancer les fixtures | Upsert, aucune duplication | cli |
| Teardown | `doctrine:database:drop --force --if-exists` + recréer | Base vide | cli |

## Tasks to do

### `1)` Entités du domaine

> Agrégats en `src/Domain/Entity`, dépôts en ports (`Domain/Repository`) + implémentation `Infrastructure`.

1. `User` : email, password, roles, totpSecret, mustChangePassword
2. `Page` : slug unique, title, status, `draft` JSON (copie de travail), `published` JSON, publishedAt, updatedAt
3. `Block` : page, type, position, payload JSON
4. `ContentVersion` : page, snapshot JSON, label, author, createdAt
5. `Media` : path, alt, mime, size, createdAt
6. Ports : `PageRepositoryInterface` (Domain) + `PageRepository` (Infrastructure, Doctrine)

### `2)` Migrations ordonnées par année et mois

> Convention verrouillée.

1. `config/packages/doctrine_migrations.yaml` : `organize_migrations: BY_YEAR_AND_MONTH`
2. Première migration sous `migrations/2026/11/`
3. `doctrine:schema:validate` → mapping et base OK

### `3)` Fixtures minimales

> AUCUN import WordPress : le contenu en ligne est quasi vide, il sera saisi via le dashboard.

1. `AppFixtures` : 1 page `accueil` en brouillon avec un bloc hero vide, 0 média
2. `app:seed-users` : admin (Emmanuel) + editor (Timothée), mots de passe provisoires, `mustChangePassword=true`

### `4)` Service brouillon (Application)

> Logique métier hors des contrôleurs.

1. `DraftService::getWorking(Page)` : draft si présent, sinon published
2. `DraftService::saveDraft(Page, payload)` : écrit `draft`, met à jour `updatedAt`

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | Les 5 entités existent, `doctrine:schema:validate` OK, index sur `page.slug` |
| 2 | La migration vit sous `migrations/2026/11/` et une seconde migration irait en `2026/12/` |
| 3 | Après fixtures : 2 users (admin, editor) + 1 page en draft ; relance = idempotent |
| 4 | `getWorking` retourne `draft` si présent, `published` sinon ; `saveDraft` ne touche jamais `published` |
