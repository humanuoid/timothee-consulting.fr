---
status: pending
---

# Instruction: Modèle de données Doctrine + import du contenu WordPress actuel

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ src/Entity/User.php
├── ✅ src/Entity/Page.php
├── ✅ src/Entity/Block.php
├── ✅ src/Entity/ContentVersion.php
├── ✅ src/Entity/Media.php
├── ✅ migrations/Version20261104100000.php
├── ✅ src/Command/ImportWordpressCommand.php
├── ✅ src/Command/SeedUsersCommand.php
└── ✅ fixtures/preprod_seed.php
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Base vide migrée (phase 1), accès à preprod.timothee-consulting.fr | Schéma présent, 0 ligne | cli |
| Happy path | `php bin/console app:import-wordpress` | Page `accueil` créée en `published` avec les blocs hero, parcours, compétences, projets, contact | cli |
| Edge case | Relancer la commande d'import | Upsert par slug : aucune duplication, contenu identique | cli |
| Teardown | `doctrine:database:drop --force` puis recréer | Base vide, prête pour un nouvel import | cli |

## Tasks to do

### `1)` Entités Doctrine

> Le contenu du site vit en BDD et est versionné par snapshots.

1. `User` : email, password (hasher), roles JSON, mustChangePassword
2. `Page` : slug unique, title, status (draft ou published), draft JSON (copie de travail), published JSON, publishedAt, updatedAt
3. `Block` : page, type, position, payload JSON (liste ordonnée de blocs par page)
4. `ContentVersion` : page, snapshot JSON, label, author, createdAt
5. `Media` : path, alt, mime, size, createdAt

### `2)` Migration

> Schéma MariaDB propre.

1. `doctrine:migrations:diff` puis revue du SQL généré
2. `doctrine:migrations:migrate` sur la base o2switch

### `3)` Import du contenu actuel

> La preprod WordPress devient la graine du nouveau site.

1. `app:import-wordpress` : exporte le contenu de https://preprod.timothee-consulting.fr (textes + images)
2. Crée la page `accueil` (published) + ses blocs ; télécharge les images dans `public/uploads/`
3. Idempotent : upsert par slug

### `4)` Seed des utilisateurs

> Deux comptes pour démarrer.

1. `app:seed-users` : admin (Emmanuel) + editor (Timothée), mots de passe provisoires à changer à la première connexion

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | Les 5 tables existent après migration, avec index sur `page.slug` et `content_version.page_id` |
| 2 | `doctrine:schema:validate` répond que le mapping et la base sont OK |
| 3 | Après import, la page accueil contient les 5 sections et les images sont dans `public/uploads/` |
| 4 | Deux users existent avec les bons rôles et des mots de passe hashés |
