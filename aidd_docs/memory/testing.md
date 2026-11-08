# Testing

## Strategy

- Tests unitaires et **tests d'architecture** avec **Pest** (https://pestphp.com/docs/arch-testing), pas PHPUnit brut.
- Pest arch testing : règles d'architecture codées (ex. `src/Domain` ne dépend d'aucun namespace Symfony, Doctrine ou Infrastructure).
- **On ne teste que la couche Domain, et seulement quand c'est nécessaire** : pas de tests systématiques sur toutes les couches.

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

- Entry: `docker compose up -d` puis `symfony server:start` → http://127.0.0.1:8000
- Auth: compte éditeur de test chargé par fixtures
- State: fixtures rechargeables via `doctrine:fixtures:load`
