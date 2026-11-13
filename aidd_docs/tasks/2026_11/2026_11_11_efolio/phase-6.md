---
status: pending
---

# Instruction: Page unique — éditeur + brouillon/publication par langue

## Architecture projection

```txt
.
├── ✅ src/Presentation/Controller/Admin/PageController.php
├── ✅ src/Presentation/Controller/Admin/AutosaveController.php
├── ✅ src/Presentation/LiveComponent/PageEditor.php
├── ✅ src/Application/Service/DraftService.php
├── ✅ src/Application/Service/PublishService.php
├── ✅ src/Domain/Event/EventNames.php
├── ✅ src/Domain/Event/DraftSaved.php
├── ✅ src/Domain/Event/ContentPublished.php
├── ✅ templates/admin/page/edit.html.twig
├── ✅ assets/controllers/autosave_controller.ts
└── ✅ tests/Feature/Admin/PageEditorTest.php
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| -------- | ------- | ------------------- | ----- |
| Setup | Page accueil en brouillon (fixtures), connecté en editor | Éditeur ouvert | browser |
| Happy path | Modifier le hero FR, attendre 1 s | « Enregistré à HH:MM:SS », draft à jour en base, published inchangé | browser, cli |
| Edge case | Slug EN dupliqué d'un autre EN ; réseau coupé pendant l'autosave | Erreur validation ; indicateur « hors ligne », resync au retour | browser |
| Teardown | Restaurer le draft initial | Contenu de départ | cli |

## Wireframe

```txt
┌──────────────────────────────────────────────────────┐
│ Page « accueil » · (1) [FR] [EN] · (2) Enregistré 12:03 │
├──────────────────────────────────────────────────────┤
│ (3) slug · titre · metaTitle · metaDescription        │
│ (4) [header ▾] [hero ▾] [parcours ▾] [competences ▾] │
│     [projets ▾] [contact ▾] — Accordion, formulaires  │
│ (5) Publier FR · Publier EN                           │
└──────────────────────────────────────────────────────┘
```

1. Onglets de langue : état indépendant par locale.
2. Indicateur autosave (Toast).
3. Champs par traduction.
4. Slots du modèle : Accordion, formulaire dynamique par type ; champs markdown = éditeur visuel du spike.
5. Publication par langue, badge de statut par langue.

## Tasks to do

### `1)` DraftService

> Le brouillon ne touche jamais published.

1. `getWorking(content)` : draft sinon published
2. `saveDraft(page, locale, payload)` : n'écrit que draft + dispatch `DraftSaved` (constantes `EventNames`)

### `2)` PageController + PageEditor

> L'éditeur de la page unique, onglets par langue.

1. Édition de la page unique, onglets FR/EN (Tab Bootstrap 6)
2. Formulaires dynamiques par slot (ComponentFormBuilder), Accordion par composant
3. Autosave : debounce 1 s → POST → `DraftService::saveDraft` ; indicateur « Enregistré à HH:MM:SS » / « hors ligne »

### `3)` PublishService

> Publication = copie + événement, pas un flip de statut.

1. `publish(page, locale)` : copie draft → published + publishedAt (statut dérivé : `isPublished()`, phase 2) + dispatch `ContentPublished`
2. Boutons Publier par langue ; badge de statut par langue

### `4)` Éditeur markdown branché

> Le champ markdown saisit en visuel, persiste en markdown.

1. Champs markdown = éditeur retenu en phase 4 ; contenu markdown persisté, jamais de HTML

## Test acceptance criteria

| Task | Acceptance criteria |
| ---- | ------------------- |
| 1 | `saveDraft` n'écrit jamais published (test unitaire) ; 3 frappes rapides → 1 seul POST |
| 2 | Onglets FR/EN indépendants ; le draft EN ne touche pas le FR publié |
| 3 | Publier FR laisse EN en brouillon ; `ContentPublished` dispatché (test) ; slug EN dupliqué d'un autre EN → erreur, d'un FR → OK |
| 4 | Le champ markdown persiste du markdown, pas du HTML |
