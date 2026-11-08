# Ecosystem

```mermaid
flowchart LR
    Human([Emmanuel, propriétaire])
    Editor([Timothée, éditeur])
    Agent([Agent IA OpenCode])
    App([App Symfony])
    Gh["GitHub public MIT · vcs.md"]
    O2["o2switch prod: site + admin + MariaDB + Redis natifs · deployment.md"]
    Local["Docker compose local: MariaDB, Redis, DevMailer"]
    CI["GitHub Actions: tests/lint sur push, security hebdo, health 3h00"]
    Cli["Symfony CLI: symfony server local + LSP standalone branché à OpenCode"]

    Agent -- "git cli" --> Gh
    Human -- "ssh, clés perso" --> O2
    Agent -- "CLI Go tools/deploy, ssh" --> O2
    Editor -- "web /admin" --> App
    Human -- "web" --> App
    App -- "sql local" --> Local
    App -- "prod sql + cache" --> O2
    Gh -- "push" --> CI
    Cli -- "sert en local" --> App
```

## Stack exacte

- PHP (Homebrew en local), Symfony LTS installé via **Symfony CLI**, Symfony LSP standalone ajouté comme LSP à OpenCode
- MariaDB + Redis : Docker compose en local, natifs o2switch en prod
- DevMailer présent dans `compose.yaml` (mails de dev en local)
