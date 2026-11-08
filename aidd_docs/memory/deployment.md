# Deployment

## Pipeline

- GitHub Actions sur chaque push : lint + tests. Hebdomadaire : `symfony security:check`. Quotidien à 3h00 : health check de la prod.
- Déploiement prod manuel, **uniquement depuis la machine du propriétaire** (clés SSH), via le CLI Go `tools/deploy` :
  1. pré-checks locaux (tests)
  2. `git push` en SSH vers le repo bare sur o2switch (`~/git/timothee-consulting.fr.git`)
  3. hook `post-receive` sur o2switch : checkout dans le docroot, `composer install --no-dev`, migrations, `cache:clear`, warmup du cache Redis
  4. health check post-déploiement depuis le CLI Go
- Interdit : rsync local → serveur ; clone GitHub depuis o2switch (DNS GitHub non autorisés par l'hébergeur) ; cron o2switch

## Environments

- local : `docker compose up -d` (MariaDB, Redis, DevMailer) + `symfony server:start` → http://127.0.0.1:8000
- prod public : `https://timothee-consulting.fr` (site servi par Symfony)
- prod admin : `https://admin.timothee-consulting.fr` (dashboard)

## Release

- Rollback code : le CLI Go re-push le commit précédent. Rollback contenu : restore d'une version + invalidation du tag Redis.

## Monitoring

- `GET /health` sur admin.… (bdd, redis, version PHP) ; ping quotidien 3h00 par GitHub Actions
- Redis géré par o2switch : s'il tombe, le site continue sans cache (fallback BDD, voir `architecture.md`)
