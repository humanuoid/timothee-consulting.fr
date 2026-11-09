# VCS

## Setup

- Main branch: `main`
- Platform: GitHub, dépôt **public**, licence MIT (`LICENSE`)
- Un seul dépôt pour code + config + docs (pas de submodule : le contenu vit en BDD)
- Hooks: aucun hook local ; CI GitHub Actions sur chaque push

## Branches

- Format: `type/short-description` (ex. `feat/page-editor`)
- Types: conventional commits (`feat`, `fix`, `docs`, `chore`, `refactor`, `test`, `ci`, `perf`)

## Commits

- Convention: conventional commits
- Format: `type(scope): description`
- Règles: message en **anglais**, impératif, minuscules, sujet ≤ 72 caractères, une concern par commit

## Commit Strategy

- AI should auto commit: `after phase`
