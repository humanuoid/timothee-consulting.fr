---
status: pending
---

# Instruction: Backoffice — squelette + modèles + médias

## Architecture projection

```txt
.
├── ✏️ composer.json
├── ✏️ importmap.php
├── ✅ assets/admin.ts
├── ✅ assets/styles/admin.css
├── ✅ templates/admin/base.html.twig
├── ✅ templates/admin/dashboard.html.twig
├── ✅ templates/admin/user/index.html.twig
├── ✅ templates/admin/page_model/index.html.twig
├── ✅ templates/admin/page_model/form.html.twig
├── ✅ templates/admin/media/index.html.twig
├── ✅ src/Presentation/Controller/Admin/DashboardController.php
├── ✅ src/Presentation/Controller/Admin/UserController.php
├── ✅ src/Presentation/Controller/Admin/PageModelController.php
├── ✅ src/Presentation/Controller/Admin/MediaController.php
├── ✅ src/Presentation/LiveComponent/SlotEditor.php
└── ✅ tests/Feature/Admin/BackofficeTest.php
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| -------- | ------- | ------------------- | ----- |
| Setup | Connecté en admin, phase 4 OK | Backoffice accessible | browser |
| Happy path | Créer un modèle à 6 slots, uploader un PDF et un PNG | Modèle persisté dans l'ordre ; médias listés avec aperçu | browser |
| Edge case | Upload .php ; alt vide ; suppression d'un média référencé ; editor sur CRUD users | Rejet ; erreur validation ; refus ; 403 | browser, api |
| Teardown | Supprimer les entités de test | Listes à l'état initial | browser |

## Wireframe

```txt
┌────────────────────────────────────────────────────┐
│ (1) TM · Modèles · Pages · Médias · Users · (2) user │
├────────────────────────────────────────────────────┤
│ (3) Modèle « one-page »                            │
│ (4) Slots : [header ▾] [hero ▾] [parcours ▾] …     │
│     ↑↓ réordonner · ✕ retirer · + ajouter          │
└────────────────────────────────────────────────────┘
```

1. Topbar : logo, navigation principale, menu utilisateur.
2. Menu utilisateur : profil, changement de mot de passe, déconnexion.
3. Édition du modèle : nom, clé.
4. Slots ordonnés : ajouter depuis la registry, réordonner, retirer — Live Component réactif.

## Tasks to do

### `1)` Live Components + Bootstrap 6 + icons en local

> Socle du backoffice réactif ; épinglé, auto-hébergé, zéro requête externe au runtime.

1. `composer require symfony/ux-live-component` — requis par SlotEditor et PageEditor
2. `importmap:require bootstrap@6.0.0-alpha.1` + bootstrap-icons épinglés → assets/vendor local
3. `assets/admin.ts` : import Bootstrap (JS) ; `assets/styles/admin.css` : tokens maison par-dessus les variables --bs-*
4. Color mode clair par défaut ; dark mode natif Bootstrap 6 disponible (toggle optionnel)

### `2)` Squelette admin

> Hyper sobre, hyper moderne : Bootstrap 6 + tokens maison, rien d'autre.

1. `templates/admin/base.html.twig` : topbar, nav (Modèles, Pages, Médias, Users), flash en Toasts, confirmations en Dialog
2. Dashboard : cards compteurs (page, médias, statut de publication par langue)

### `3)` CRUD Users custom

> Pas d'EasyAdmin : écrans standard écrits à la main.

1. Liste, création, édition, rôles, régénération des codes de secours TOTP — réservé ROLE_ADMIN

### `4)` CRUD PageModel

> La composition vit dans le backoffice (option B).

1. Liste + formulaire : nom, clé unique
2. `SlotEditor` (Live Component) : add / remove / reorder des slots depuis la registry

### `5)` CRUD Médias

> CV/DC sont des PDF : l'allowlist s'élargit.

1. Upload : finfo allowlist png/jpeg/webp + pdf, ≤ 5 Mo, nom aléatoire, altText obligatoire
2. Liste avec aperçu, suppression avec contrôle d'usage (média référencé → refus)

## Test acceptance criteria

| Task | Acceptance criteria |
| ---- | ------------------- |
| 1 | `importmap:install` télécharge en local ; audit réseau du backoffice : aucune requête externe |
| 2 | Topbar, toasts et dialog fonctionnels ; responsive |
| 3 | CRUD users réservé ROLE_ADMIN (editor → 403) |
| 4 | Un modèle à 6 slots persiste dans l'ordre choisi |
| 5 | Upload .php et .svg rejetés ; png et pdf acceptés ; alt vide → erreur ; suppression d'un média référencé refusée |
