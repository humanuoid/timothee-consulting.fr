---
status: pending
---

# Instruction: Dashboard — liste/CRUD des pages en custom (Live Component) + médias via EasyAdmin

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ src/Presentation/Controller/Admin/PageAdminController.php
├── ✅ src/Presentation/LiveComponent/PageList.php
├── ✅ src/Presentation/Form/PageType.php
├── ✅ templates/admin/page/index.html.twig
├── ✅ templates/admin/page/form.html.twig
├── ✅ templates/admin/dashboard.html.twig
├── ✅ assets/controllers/page_list_controller.ts
└── ✅ public/uploads/
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Connecté en editor, contenu seedé | Dashboard accessible | browser |
| Happy path | `/admin/pages`, recherche, création d'une page, upload média dans EasyAdmin | Liste filtrée en live ; page créée en brouillon ; média uploadé et listé | browser |
| Edge case | Slug en double | Erreur de validation claire | browser |
| Teardown | Supprimer la page et le média de test | Liste à l'état initial | browser |

## Wireframe

```txt
┌───────────────────────────────────────────────┐
│ (1) Titre "Pages" · (2) recherche · (3) +     │
│     "Nouvelle page"                           │
├───────────────────────────────────────────────┤
│ (4) Liste :                                   │
│  ┌─────────────────────────────────────────┐  │
│  │ (5) accueil │ badge Brouillon │ (6) ✎ │  │
│  └─────────────────────────────────────────┘  │
└───────────────────────────────────────────────┘
```

1. Titre de la section.
2. Recherche live par titre ou slug.
3. Bouton "Nouvelle page".
4. Liste des pages triée par date de modification.
5. Ligne page : titre, slug, badge de statut.
6. Actions : Éditer, Aperçu, Publier, Supprimer.

## Tasks to do

### `1)` Liste des pages en Live Component

> Réactive, sans rechargement.

1. `PageList` : tableau des pages (titre, slug, statut, updatedAt), recherche live (champ `q`)
2. Contrôleur Stimulus en TS pour les interactions

### `2)` CRUD page (custom)

> L'éditeur de blocs est custom, donc la fiche page aussi.

1. `PageType` : title (slug auto, unique), statut par défaut `draft`
2. `PageAdminController` : new, edit, delete avec confirmation

### `3)` Médias via EasyAdmin

> Le CRUD standard va vite avec EasyAdmin.

1. `MediaCrudController` (phase 3) : upload (validation mime/taille) dans `public/uploads/`, aperçu, suppression avec vérification d'usage

### `4)` Dashboard d'accueil custom

> `/admin` : raccourcis et compteurs.

1. `templates/admin/dashboard.html.twig` : cards (pages, médias, dernière publication, health)

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | La liste affiche les pages seedées ; la recherche filtre sans rechargement |
| 2 | Création "À propos" → slug `a-propos`, statut brouillon ; doublon → erreur affichée |
| 3 | Upload PNG dans EasyAdmin → fichier dans `public/uploads/` + aperçu |
| 4 | `/admin` affiche les cards avec les bons compteurs |
