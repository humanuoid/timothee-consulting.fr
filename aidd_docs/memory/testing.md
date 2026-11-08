# Testing

## Strategy

- Unit : services du domaine/application (PHPUnit)
- Fonctionnel : contrôleurs public et admin (WebTestCase du client Symfony + fixtures)
- Chaque phase du plan a son Test Scope (voir `aidd_docs/tasks/`)

## Tools

- PHPUnit (`vendor/bin/phpunit`), fixtures Doctrine

## Conventions

- Tests dans `tests/`, en miroir de `src/`
- Tout nouveau comportement a un test avant le commit

## Run

- `vendor/bin/phpunit`

## Browser QA

- Entry: `docker compose up -d` puis `symfony server:start` → http://127.0.0.1:8000
- Auth: compte éditeur de test chargé par fixtures
- State: fixtures rechargeables via `doctrine:fixtures:load`
