# Auth

## Authentication

- Login par formulaire + session cookie (Symfony Security), mot de passe hashé
- **2FA TOTP obligatoire** sur le dashboard (app authenticator), secret par utilisateur
- Reset password par email (bundle reset-password), lien signé à usage unique
- Rate limiter sur `/login` (5 essais / 5 min par IP + username), honeypot, CSRF sur tous les formulaires
- Changement de mot de passe obligatoire à la première connexion

## Authorization

- Rôles : `ROLE_ADMIN` (tout, dont gestion des users), `ROLE_EDITOR` (éditer et publier le contenu)
- Enforcement : `#[IsGranted]` sur les contrôleurs admin, firewall dédié au préfixe `/admin`

## Sessions

- Session cookie PHP, invalidation au logout, durée standard
