---
status: pending
---

# Instruction: Dashboard admin — liste des pages + gestion des médias

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ src/Controller/Admin/PageAdminController.php
├── ✅ src/Controller/Admin/MediaController.php
├── ✅ src/LiveComponent/PageList.php
├── ✅ src/Form/PageType.php
├── ✅ src/Form/MediaUploadType.php
├── ✅ templates/admin/page/index.html.twig
├── ✅ templates/admin/page/form.html.twig
├── ✅ templates/admin/media/index.html.twig
├── ✅ templates/admin/dashboard.html.twig
└── ✅ public/uploads/
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Users et contenu seedés, connecté en editor | Dashboard accessible | browser |
| Happy path | `/admin/pages` puis upload d'une image | Liste avec badges brouillon/publié ; image uploadée et listée | browser |
| Edge case | Créer une page avec un slug déjà pris | Erreur de validation claire, pas de doublon | browser |
| Teardown | Supprimer la page et le média de test | Liste revenue à l'état initial | browser |

## Wireframe

```txt
┌───────────────────────────────────────────────┐
│ (1) Titre "Pages" · (2) recherche · (3) +     │
│     "Nouvelle page"                           │
├───────────────────────────────────────────────┤
│ (4) Liste des pages :                         │
│  ┌─────────────────────────────────────────┐  │
│  │ (5) accueil │ badge Publié │ (6) actions│  │
│  └─────────────────────────────────────────┘  │
└───────────────────────────────────────────────┘
```

1. Titre de la section.
2. Recherche/filtre par titre ou slug.
3. Bouton "Nouvelle page".
4. Liste des pages, triée par date de modification.
5. Ligne page : titre, slug, badge de statut.
6. Actions : Éditer, Aperçu, Publier, Supprimer.

## Tasks to do

### `1)` Liste des pages en Live Component

> Réactive, sans rechargement de page.

1. `PageList` : tableau des pages avec statut et updatedAt
2. Recherche live (champ `q`)

### `2)` CRUD page

> Créer, renommer, supprimer ; slug auto depuis le titre.

1. `PageType` : title (slug auto et unique), status par défaut `draft`
2. Contrôleur : new, edit, delete avec confirmation

### `3)` Upload de médias

> Images du site, stockées localement.

1. `MediaUploadType` + `MediaController` : upload (validation mime/taille), stockage dans `public/uploads/`, entrée `Media`
2. Galerie avec aperçu, copie du chemin, suppression avec vérification d'usage

### `4)` Dashboard d'accueil

> `/admin` affiche les raccourcis et les compteurs.

1. `AdminController::dashboard` : cards (pages, médias, dernière publication)

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | La liste affiche les pages seedées ; la recherche filtre sans rechargement |
| 2 | Création d'une page "Test" → slug `test`, statut brouillon ; slug en double → erreur affichée |
| 3 | Upload d'un PNG → fichier dans `public/uploads/` + ligne Media + aperçu affiché |
| 4 | `/admin` affiche les cards avec les bons compteurs |
