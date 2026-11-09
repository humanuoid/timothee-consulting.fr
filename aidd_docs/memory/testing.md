# Testing

## Strategy

- Tests unitaires et **tests d'architecture** avec **Pest** (https://pestphp.com/docs/arch-testing), pas PHPUnit brut.
- Pest arch testing : règles d'architecture codées (ex. `src/Domain` ne dépend d'aucun namespace Symfony, Doctrine ou Infrastructure). Ces tests d'arch portent sur **toutes les couches**.
- **Tests unitaires : couche Domain uniquement, et seulement quand c'est nécessaire.** Cette restriction ne s'applique pas aux tests d'architecture.

## Tools

- Pest (`vendor/bin/pest`) : unit + arch
- Fixtures Doctrine pour les jeux de données

## Conventions

- Tests dans `tests/`, en miroir de `src/`
- Un test d'arch par règle d'architecture de `architecture.md` (dépendance vers l'intérieur, pas de framework dans Domain)
- Un test unitaire Domain quand la logique métier n'est pas évidente

## Run

- `vendor/bin/pest` (lance unit + arch)
- `vendor/bin/pest --group=arch` (arch seul)

## Browser QA

- Entry: `docker compose up -d` → https://localhost (conteneur FrankenPHP `app`)
- Auth: compte éditeur de test chargé par fixtures
- State: fixtures rechargeables via `doctrine:fixtures:load`
