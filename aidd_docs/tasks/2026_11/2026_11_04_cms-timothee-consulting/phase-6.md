---
status: pending
---

# Instruction: Éditeur de blocs (Live Component) avec autosave du brouillon

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ src/LiveComponent/PageEditor.php
├── ✅ src/Controller/Admin/PageEditorController.php
├── ✅ src/Service/DraftService.php
├── ✅ src/Form/Block/HeroBlockType.php
├── ✅ src/Form/Block/RichTextBlockType.php
├── ✅ src/Form/Block/ParcoursBlockType.php
├── ✅ src/Form/Block/CompetencesBlockType.php
├── ✅ src/Form/Block/ProjetsBlockType.php
├── ✅ src/Form/Block/ContactBlockType.php
├── ✅ templates/admin/page/edit.html.twig
├── ✅ assets/controllers/editor_controller.js
└── ✅ assets/controllers/autosave_controller.js
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Page accueil avec brouillon, connecté en editor | Éditeur ouvert sur la page | browser |
| Happy path | Modifier le titre du hero, attendre 1 s | "Enregistré à HH:MM:SS", `Page.draft` à jour en BDD, `published` inchangé | browser, cli |
| Edge case | Couper le réseau pendant l'autosave | Indicateur "hors ligne", resynchronisation au retour | browser |
| Teardown | Restaurer le snapshot initial | Contenu revenu à l'état de départ | browser |

## Wireframe

```txt
┌───────────────────────────────────────────────┐
│ (1) Barre : titre page · badge Brouillon ·    │
│     (2) "Enregistré à …" · (3) Publier        │
├──────────────┬────────────────────────────────┤
│ (4) Liste    │ (5) Formulaire du bloc         │
│ des blocs    │     sélectionné                │
│  Hero        │   champs selon le type         │
│  Parcours    │   (texte, image, liste…)       │
│  …           │                                │
│ (6) + Bloc   │                                │
└──────────────┴────────────────────────────────┘
```

1. Barre d'édition : nom de la page, badge de statut.
2. Indicateur d'autosave (heure du dernier enregistrement).
3. Bouton "Publier" (mis en œuvre en phase 8).
4. Liste ordonnée des blocs : monter, descendre, supprimer.
5. Formulaire du bloc sélectionné, champs adaptés au type.
6. Bouton "Ajouter un bloc" avec choix du type.

## Tasks to do

### `1)` Copie de travail brouillon

> Le public ne voit que `published`.

1. `Page.draft` (JSON) = copie de travail ; `Page.published` = version en ligne
2. `DraftService` : `getWorking(Page)` (draft si présent, sinon published), `saveDraft(Page, payload)`

### `2)` Éditeur de blocs en Live Component

> Pas de gros formulaire : une page = une liste ordonnée de blocs.

1. `PageEditor` : liste des blocs (type, position), actions ajouter, supprimer, monter, descendre
2. Sélection d'un bloc → formulaire typé (`HeroBlockType`, `RichTextBlockType`, …)
3. Champs structurés stockés en JSON dans le payload du bloc

### `3)` Champ texte riche

> WYSIWYG léger, pas un textarea brut.

1. Intégrer un éditeur riche (TipTap via AssetMapper, ou Trix) pour les blocs `richtext`
2. Stockage du contenu en JSON de l'éditeur, HTML nettoyé au rendu

### `4)` Autosave

> Environ 1 s après la dernière frappe.

1. Stimulus `autosave_controller` : debounce 1000 ms → POST `/admin/pages/{id}/autosave` (payload JSON)
2. Contrôleur : écrit `Page.draft` via DraftService, répond `{savedAt}`
3. Indicateur visuel "Enregistré à HH:MM:SS" / "hors ligne"

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | Après sauvegarde, `published` ne change pas et `draft` oui ; une page sans draft sert `published` |
| 2 | Ajouter, déplacer ou supprimer un bloc met à jour l'ordre rendu ; le formulaire affiché dépend du type choisi |
| 3 | Le bloc richtext enregistre du contenu formaté (gras, listes) et le rend correctement côté site |
| 4 | 3 frappes rapides → 1 seul appel autosave ; BDD à jour ; l'indicateur affiche l'heure |
