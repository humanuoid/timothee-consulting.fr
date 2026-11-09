---
status: pending
---

# Instruction: Éditeur de blocs (Live Component) + autosave du brouillon, en Stimulus/TypeScript

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ src/Presentation/LiveComponent/PageEditor.php
├── ✅ src/Presentation/Controller/Admin/PageEditorController.php
├── ✅ src/Presentation/Form/Block/HeroBlockType.php
├── ✅ src/Presentation/Form/Block/RichTextBlockType.php
├── ✅ src/Presentation/Form/Block/ParcoursBlockType.php
├── ✅ src/Presentation/Form/Block/CompetencesBlockType.php
├── ✅ src/Presentation/Form/Block/ProjetsBlockType.php
├── ✅ src/Presentation/Form/Block/ContactBlockType.php
├── ✅ templates/admin/page/edit.html.twig
├── ✅ assets/controllers/editor_controller.ts
├── ✅ assets/controllers/autosave_controller.ts
└── ✅ assets/richtext.ts
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Page accueil en brouillon, connecté en editor | Éditeur ouvert | browser |
| Happy path | Modifier le titre du hero, attendre 1 s | "Enregistré à HH:MM:SS", `draft` à jour en BDD, `published` inchangé | browser, cli |
| Edge case | Couper le réseau pendant l'autosave | Indicateur "hors ligne", resync au retour | browser |
| Teardown | Restore du snapshot initial (phase 7) | Contenu initial | browser |

## Wireframe

```txt
┌───────────────────────────────────────────────┐
│ (1) Barre : titre page · badge Brouillon ·    │
│     (2) "Enregistré à …" · (3) Publier        │
├──────────────┬────────────────────────────────┤
│ (4) Liste    │ (5) Formulaire du bloc         │
│ des blocs    │     sélectionné                │
│  Hero        │   champs selon le type (TS)    │
│  Parcours    │                                │
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

### `1)` Éditeur de blocs en Live Component

> Pas de gros formulaire : une page = une liste ordonnée de blocs.

1. `PageEditor` : liste des blocs (type, position), actions ajouter/supprimer/monter/descendre
2. Sélection d'un bloc → formulaire typé (`HeroBlockType`, `RichTextBlockType`, …)
3. Payload JSON par bloc, persisté via `DraftService::saveDraft`

### `2)` Champ texte riche

> WYSIWYG léger, pas un textarea brut.

1. Éditeur riche (TipTap via importmap asset mapper, zéro build Node) pour les blocs `richtext`, contenu en JSON
2. HTML nettoyé au rendu côté `SiteRenderer`

### `3)` Autosave en TypeScript

> ~1 s après la dernière frappe.

1. `autosave_controller.ts` (Stimulus) : debounce 1000 ms → POST `/admin/pages/{id}/autosave`
2. Contrôleur : `DraftService::saveDraft`, répond `{savedAt}`
3. Indicateur "Enregistré à HH:MM:SS" / "hors ligne" (gestion d'erreur réseau)

### `4)` Blocs : types du portfolio

> Couvre les sections du site actuel.

1. Types : hero, parcours, compétences, projets, contact, richtext
2. Chaque type a son partial Twig (phase 4) et son formulaire admin

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | Ajouter/déplacer/supprimer un bloc met à jour l'ordre ; le formulaire affiché dépend du type choisi |
| 2 | Le bloc richtext enregistre du contenu formaté, rendu proprement côté public |
| 3 | 3 frappes rapides → 1 seul POST autosave ; BDD à jour ; indicateur à l'heure |
| 4 | Les 6 types de bloc ont formulaire admin + rendu public sans erreur |
