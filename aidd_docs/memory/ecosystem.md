# Ecosystem

```mermaid
flowchart LR
    Human([Emmanuel, propriétaire])
    Editor([Timothée, éditeur])
    Agent([Agent IA OpenCode])
    App([App Symfony])
    Gh["GitHub public MIT · vcs.md"]
    O2["o2switch prod: site + admin + MariaDB + Redis natifs · deployment.md"]
    Local["Docker compose local: app FrankenPHP (https://localhost), MariaDB, Redis, DevMailer, watch TS"]
    CI["GitHub Actions: tests/lint sur push, security hebdo, health 3h00"]
    Cli["Symfony CLI: commandes console + LSP standalone branché à OpenCode"]

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

- PHP 8.4 (Homebrew, via `.php-version`) pour CLI/tests, Symfony LTS installé via **Symfony CLI**, Symfony LSP standalone dans `~/.local/bin` branché à OpenCode
- Serveur web local : conteneur **FrankenPHP** `app` dans docker compose (config `.docker/frankenphp/`, image épinglée `1.13.5-php8.4.27-alpine` = PHP 8.4.27, même version que `.php-version`) → https://localhost (HTTPS auto, ports 80/443)
- MariaDB + Redis : Docker compose en local, natifs o2switch en prod
- DevMailer présent dans `compose.yaml` (mails de dev en local)
