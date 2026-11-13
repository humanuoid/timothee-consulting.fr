# Graphify

## What it is

Graphify transforme la codebase en graphe de connaissances interrogeable (`graphify-out/graph.json` + `GRAPH_REPORT.md` + `graph.html`). Parsing AST local par tree-sitter (déterministe, aucun LLM, rien ne quitte la machine pour le code). Installé sur ce projet pour OpenCode.

## Installation

- CLI : `uv tool install graphifyy` (package PyPI officiel, double-y). Binaire dans `~/.local/bin/graphify` (PATH via `~/.zshenv`, créé par `uv tool update-shell`).
- Skill OpenCode au niveau projet : `graphify install --project --platform opencode` a écrit `.opencode/skills/graphify/` (SKILL.md + references/), une section `## graphify` dans `AGENTS.md`, et le plugin `.opencode/plugins/graphify.js`. Le plugin insère seulement un rappel avant la première commande shell d'une session — il ne rebuild rien.
- `graphify-out/` est gitignoré : le graphe est régénérable localement, pas commité (un graphe commité serait réécrit en entier à chaque build = diff énorme à chaque commit).

## Build et mise à jour

```bash
graphify extract . --code-only     # build initial 100% local (code seul, docs/PDF exclus)
graphify cluster-only . --no-label  # rapport + graphe sans nommage LLM des communautés
graphify update .                   # rebuild incrémental après modif de code (AST seul, 0 crédit API)
```

Nommage des communautés par LLM (optionnel, nécessite une clé API) : `graphify label .` (sans `--no-label`).

## Auto-rebuild (hooks git)

Installés une fois pour toutes via `graphify hook install` (officiel, « no background process needed ») :

- **post-commit** : rebuild du graphe à chaque commit (fichiers code uniquement, via `git diff HEAD~1` ; AST local, 0 LLM)
- **post-checkout / post-merge / post-rewrite** : resync au changement de branche et aux fusions
- **merge driver** : inscrit `graphify-out/graph.json merge=graphify` dans `.gitattributes` — inerte ici puisque `graphify-out/` est gitignoré
- Dans un worktree, le hook sort silencieusement (gitdir ≠ commondir) : seuls les commits du checkout principal rebuildent le graphe
- Docs/images ignorées par le hook : `graphify update .` à la main pour elles, et pour les changements non commités en cours de session

## Gotchas

- Les tests Pest (`arch()`, `test()` en closures) ne produisent aucun symbole AST : les fichiers de `tests/` sont dans le graphe comme nœuds fichier, sans leurs règles ni fonctions. Ne pas s'y fier pour raisonner sur les tests.
- ~25 fichiers racine sans extracteur (dotfiles, `.env.example`, etc.) sont hors graphe — l'avertissement « not classified » du build est attendu.

## Requêtes

```bash
graphify query "<question>"     # sous-graphe scopé (BFS, budget 2000 tokens par défaut)
graphify path "A" "B"           # plus court chemin entre deux nœuds
graphify explain "Concept"      # nœud + ses voisins
graphify god-nodes --top 10     # hubs architecturaux
```

Exit codes : 0 = réponse, 3 = nom ambigu, 4 = aucun nœud. Config par projet via `.graphifyrc` (`query_budget`, `query_depth`, `language.<ext>`).

## Fichiers

- `.graphifyignore` : exclusions propres à graphify (vendor/, var/, node_modules/, graphify-out/, .env*...)
- `graphify-out/` : graph.json, GRAPH_REPORT.md, graph.html, cache AST, manifest

## Options non retenues

- Extras (`[pdf]`, `[mcp]`, `[watch]`...) : non installés, à ajouter si besoin via `uv tool install "graphifyy[extra]" --reinstall`. Le mode `--watch` est inutile ici : les hooks git couvrent déjà le rebuild automatique, sans process permanent.
