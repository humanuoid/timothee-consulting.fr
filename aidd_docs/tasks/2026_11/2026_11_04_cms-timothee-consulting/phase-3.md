---
status: pending
---

# Instruction: Sécurité renforcée (TOTP 2FA, rate limiter, reset password) + EasyAdminBundle comme socle CRUD

## Architecture projection

> Tree of the final files. ✅ create · ✏️ modify · ❌ delete

```txt
.
├── ✅ config/packages/security.yaml
├── ✅ config/packages/rate_limiter.yaml
├── ✅ config/packages/scheb_2fa.yaml
├── ✅ src/Presentation/Controller/SecurityController.php
├── ✅ src/Infrastructure/Security/LoginFormAuthenticator.php
├── ✅ src/Presentation/Controller/Admin/AdminDashboardController.php
├── ✅ src/Presentation/Controller/Admin/UserCrudController.php
├── ✅ src/Presentation/Controller/Admin/MediaCrudController.php
├── ✅ src/Presentation/Controller/ProfileController.php
├── ✅ src/Presentation/Controller/ResetPasswordController.php
├── ✅ templates/security/login.html.twig
├── ✅ templates/security/totp.html.twig
├── ✅ templates/admin/base.html.twig
├── ✅ templates/bundles/EasyAdminBundle/layout.html.twig
├── ✅ assets/admin.ts
└── ✅ config/packages/mailer.yaml
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| --- | --- | --- | --- |
| Setup | Users seedés (phase 2), serveur local lancé | Page de login accessible | browser |
| Happy path | Login + code TOTP valide | Redirection `/admin`, session + 2FA vérifiée | browser |
| Edge case | 5 logins échoués ; POST sans CSRF ; mauvais code TOTP | HTTP 429 après 5 essais ; 403 sans CSRF ; retour écran TOTP | browser, api |
| Teardown | Logout | `/admin` redirige vers `/login` | browser |

## Wireframe

```txt
┌───────────────────────────────────────────────┐
│ (1) Login : email · mot de passe · (2) btn    │
├───────────────────────────────────────────────┤
│ (3) 2FA : code TOTP à 6 chiffres · (4) valider│
└───────────────────────────────────────────────┘

┌───────────────────────────────────────────────┐
│ (5) Header EasyAdmin : logo · (6) user menu   │
├───────────┬───────────────────────────────────┤
│ (7) Nav   │ (8) Main : CRUD users, médias     │
│ Users     │                                   │
│ Médias    │                                   │
└───────────┴───────────────────────────────────┘
```

1. Formulaire de login : email + mot de passe + CSRF.
2. Bouton "Se connecter" (rate limiter actif).
3. Écran TOTP : champ 6 chiffres.
4. Bouton "Valider".
5. Header du dashboard : marque, lien site public.
6. Menu user : email, changer le mot de passe, déconnexion.
7. Nav EasyAdmin : Users, Médias.
8. Main : CRUD du module courant.

## Tasks to do

### `1)` Authentification de base

> Formulaire + session, hash de mot de passe.

1. `security.yaml` : provider User, firewall `admin` sur `/admin`, `custom_authenticator`, logout
2. `LoginFormAuthenticator` + `SecurityController` (`/login`, `/logout`)
3. Rôles `ROLE_ADMIN` / `ROLE_EDITOR`, `#[IsGranted]` sur les contrôleurs admin

### `2)` 2FA TOTP obligatoire

> Tout le dashboard derrière TOTP.

1. Bundle 2FA (scheb/2fa ou équivalent à jour), `totpSecret` sur User
2. Écran d'enrollment TOTP à la première connexion (QR code + secret)
3. Écran de saisie du code à chaque login, rate limiter dédié

### `3)` Reset password + changement obligatoire

> Liens signés à usage unique, mails via DevMailer en local / SMTP o2switch en prod.

1. Bundle reset-password : demande, lien signé, nouveau mot de passe
2. `mustChangePassword` : formulaire de changement forcé à la première connexion
3. `config/packages/mailer.yaml` : DevMailer local, SMTP o2switch prod

### `4)` CSRF + rate limiter

> Toutes les bonnes pratiques en vigueur.

1. CSRF sur tous les formulaires
2. Rate limiter `login` : 5 essais / 5 min par IP + username ; honeypot sur le formulaire

### `5)` EasyAdminBundle (socle CRUD)

> Users et Médias en CRUD auto ; l'éditeur de pages reste custom (phases 5-6).

1. `composer require easycorp/easyadmin-bundle`
2. `AdminDashboardController` + `UserCrudController` + `MediaCrudController`
3. Layout EasyAdmin surchargé (`templates/bundles/EasyAdminBundle/layout.html.twig`) avec `assets/admin.ts` (Stimulus + TS)

## Test acceptance criteria

| Task | Acceptance criteria |
| --- | --- |
| 1 | Login OK → écran TOTP ; mauvais mdp → retour `/login` ; editor accède à /admin, pas au CRUD users |
| 2 | Sans code TOTP valide, aucun accès au dashboard ; enrollment au premier login |
| 3 | Lien de reset reçu (DevMailer en local), à usage unique ; changement forcé effectif au premier login |
| 4 | 6e tentative en 5 min → 429 ; POST sans CSRF → 403 |
| 5 | `/admin` affiche le dashboard EasyAdmin avec Users et Médias fonctionnels |
