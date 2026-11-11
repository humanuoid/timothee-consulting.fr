---
status: pending
---

# Instruction: Sécurité (TOTP, rate limiter, reset, headers)

## Architecture projection

```txt
.
├── ✏️ composer.json
├── ✏️ config/packages/security.yaml
├── ✅ config/packages/scheb_2fa.yaml
├── ✅ config/packages/dev/scheb_2fa.yaml
├── ✅ config/packages/rate_limiter.yaml
├── ✅ src/Infrastructure/Security/LoginFormAuthenticator.php
├── ✅ src/Presentation/Controller/Security/SecurityController.php
├── ✅ src/Presentation/Controller/Security/ResetPasswordController.php
├── ✅ src/Presentation/Controller/Security/ProfileController.php
├── ✅ templates/security/login.html.twig
├── ✅ templates/security/totp.html.twig
├── ✅ templates/security/reset_password.html.twig
├── ✅ templates/security/change_password.html.twig
├── ✏️ src/Presentation/Controller/HealthController.php
├── ✅ src/Presentation/Controller/Admin/AdminHealthController.php
├── ✅ tests/Feature/Security/LoginTest.php
├── ✅ tests/Feature/Security/TotpTest.php
└── ✅ tests/Feature/Security/HealthTest.php
```

## Test Scope

| Scenario | Action | Observable outcome | Actor |
| -------- | ------- | ------------------- | ----- |
| Setup | Users seedés (phase 2), serveur local lancé | Écran de login accessible | browser |
| Happy path | Login + code TOTP valide | Redirection /admin, session + 2FA vérifiées | browser |
| Edge case | 5 logins échoués ; POST sans CSRF ; mauvais code TOTP x6 ; lien reset réutilisé | 429 ; 403 ; 429 ; refus | browser, api |
| Teardown | Logout | /admin redirige vers /login | browser |

## Tasks to do

### `1)` Authentification de base

> Formulaire + session, firewall dédié.

1. `security.yaml` : firewall `/admin` + `/login`, form login, logout
2. `LoginFormAuthenticator` + `SecurityController`
3. Rôles `ROLE_ADMIN` / `ROLE_EDITOR`, `#[IsGranted]` sur les contrôleurs admin

### `2)` TOTP obligatoire

> Tout le backoffice derrière la 2FA.

1. scheb/2fa : TOTP sur User, écran d'enrollment (QR) à la première connexion
2. Écran code 6 chiffres (composant OTP input Bootstrap 6), rate limiter dédié
3. Codes de secours hashés, générés à l'enrollment, régénérables par ROLE_ADMIN
4. Désactivée en dev : `config/packages/dev/scheb_2fa.yaml` (`totp.enabled: false`) — login direct ; active en test, staging et prod

### `3)` Reset password + changement forcé

> Liens signés à usage unique ; premier login = changement de mot de passe.

1. symfonycasts/reset-password-bundle : demande, lien signé usage unique + expiration, nouveau mot de passe
2. `mustChangePassword` : changement forcé (Password strength Bootstrap 6)

### `4)` Rate limiter + CSRF + honeypot

> Surface publique minimale.

1. Rate limiter login : 5 essais / 5 min par IP + username
2. CSRF sur tous les formulaires ; honeypot sur le login

### `5)` Headers de sécurité

> Politique zéro ressource externe = CSP stricte.

1. CSP `script-src 'self'` (aucune origine externe), `X-Content-Type-Options`, `frame-ancestors 'self'` (iframe preview), `Referrer-Policy`
2. Cookies `HttpOnly`, `Secure`, `SameSite=Lax`

### `6)` Health scindé

> Liveness publique sans fuite, détail derrière l'auth.

1. `/health` public : `{"status":"ok"}` seul — pour le cron GitHub Actions
2. `/admin/health` derrière auth : bdd, redis, version PHP

## Test acceptance criteria

| Task | Acceptance criteria |
| ---- | ------------------- |
| 1 | Anonyme sur /admin → 302 /login ; editor sans accès au CRUD users |
| 2 | Sans code TOTP valide aucun accès dashboard ; 6e code faux → 429 ; enrollment QR au premier login ; en dev, login direct sans TOTP |
| 3 | Lien reset à usage unique ; expiré → refus ; changement forcé effectif au premier login |
| 4 | 6e login en 5 min → 429 ; POST sans CSRF → 403 |
| 5 | CSP sans aucune origine externe ; cookies sécurisés |
| 6 | /health anonyme 200 {"status":"ok"} ; /admin/health anonyme → 302 |
