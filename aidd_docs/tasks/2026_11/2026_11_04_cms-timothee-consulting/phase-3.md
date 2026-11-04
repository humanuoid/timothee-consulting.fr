---
status: pending
---

# Instruction: Sécurité (login, rôles, CSRF, rate limiter) + layout admin Symfony UX

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ config/packages/security.yaml
├── ✅ config/packages/rate_limiter.yaml
├── ✅ src/Controller/SecurityController.php
├── ✅ src/Security/LoginFormAuthenticator.php
├── ✅ src/Controller/Admin/AdminController.php
├── ✅ src/Controller/Admin/ProfileController.php
├── ✅ templates/security/login.html.twig
├── ✅ templates/admin/base.html.twig
├── ✅ templates/admin/dashboard.html.twig
├── ✅ assets/app.js
└── ✅ assets/app.css
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Users seedés (phase 2), serveur lancé | Page de login accessible | browser |
| Happy path | Login avec les bons identifiants editor | Redirection vers `/admin`, session active, menu user visible | browser |
| Edge case | 5 logins échoués puis 6e tentative ; POST sans token CSRF | HTTP 429 après 5 essais en 5 min ; 403 sans CSRF | browser, api |
| Teardown | Logout, suppression de la session | `/admin` redirige vers `/login` | browser |

## Wireframe

```txt
┌───────────────────────────────────────────────┐
│ (1) Header : logo CMS · lien site · (2) user  │
├───────────┬───────────────────────────────────┤
│ (3) Nav   │ (4) Main                          │
│ - Pages   │   contenu de la page courante     │
│ - Médias  │   (liste, éditeur, historique)    │
│ - Compte  │                                   │
└───────────┴───────────────────────────────────┘

┌───────────────────────────────────────────────┐
│ (5) Login : email · mot de passe · (6) bouton │
└───────────────────────────────────────────────┘
```

1. Header admin : marque, lien vers le site public.
2. Menu user : email connecté, changer le mot de passe, déconnexion.
3. Nav : sections du dashboard (pages, médias, compte).
4. Main : contenu de la page courante.
5. Formulaire de login : email + mot de passe, token CSRF.
6. Bouton "Se connecter", rate limiter actif.

## Tasks to do

### `1)` Authentification

> Login form + session, mots de passe hashés.

1. `security.yaml` : provider sur l'entité User, firewall `main` avec `custom_authenticator`, logout
2. `LoginFormAuthenticator` + `SecurityController` (`/login`, `/logout`)
3. Template `login.html.twig`

### `2)` Rôles et droits

> ROLE_ADMIN (tout) et ROLE_EDITOR (éditer et publier le contenu).

1. Hiérarchie de rôles dans `security.yaml`
2. `#[IsGranted]` sur les contrôleurs admin

### `3)` CSRF + rate limiter

> Formulaires protégés, login throttlé.

1. CSRF activé sur tous les formulaires Symfony
2. Rate limiter `login` : 5 essais / 5 min par IP + username

### `4)` Layout admin Symfony UX

> Base Twig + Turbo + Stimulus, sans build front lourd.

1. `assets/app.js` : Stimulus + Turbo
2. `templates/admin/base.html.twig` : header, nav, main, flashes
3. `assets/app.css` : style minimal propre

### `5)` Changement de mot de passe

> Premier login = changement obligatoire.

1. `ProfileController` : formulaire (actuel + nouveau + confirmation), `mustChangePassword` sur User

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | Login OK → session + redirection `/admin` ; mauvais mot de passe → retour `/login` avec erreur |
| 2 | Un editor ne voit pas la gestion des users ; l'admin si |
| 3 | 6e tentative de login en 5 min → HTTP 429 ; POST sans token CSRF → 403 |
| 4 | `/admin` rendu avec header, nav et main, assets chargés en HTTPS |
| 5 | Changement de mot de passe effectif : ancien refusé, nouveau accepté |
