---
objective: "Livrer le moteur efolio (composants en code, modèles composés en backoffice, brouillon/publication par langue) et la one-page portfolio FR/EN de Timothée Consulting — cache Redis full-page à tags, contact asynchrone via cron o2switch — déployée en staging preprod puis en prod sur timothee-consulting.fr."
status: pending
---

# Plan: Moteur efolio — portfolio one-page Timothée Consulting

Source: Spécifications efolio fournies par l'utilisateur (document « Spécifications efolio — Timothée Consulting », session 2026-11-11) + décisions verrouillées en session (moteur à composants option B, zéro EasyAdmin, Bootstrap 6, direction B, un seul domaine, staging preprod, Messenger asynchrone).

## Phases

| #   | Phase | File | Depends on |
| --- | ----- | ---- | ---------- |
| 1   | Outillage qualité + CI (+ refactor health) | [`phase-1.md`](./phase-1.md) | none |
| 2   | Modèle de données + bindings | [`phase-2.md`](./phase-2.md) | 1 |
| 3   | Sécurité (TOTP, rate limiter, reset, headers) | [`phase-3.md`](./phase-3.md) | 2 |
| 4   | Bibliothèque de composants + markdown | [`phase-4.md`](./phase-4.md) | 2 |
| 5   | Backoffice : squelette + modèles + médias | [`phase-5.md`](./phase-5.md) | 3, 4 |
| 6   | Page unique : éditeur + brouillon/publication par langue | [`phase-6.md`](./phase-6.md) | 5 |
| 7   | Site public one-page + preview live | [`phase-7.md`](./phase-7.md) | 6 |
| 8   | Cache Redis full-page à tags | [`phase-8.md`](./phase-8.md) | 7 |
| 9   | Contact asynchrone + pages légales | [`phase-9.md`](./phase-9.md) | 7 |
| 10  | Déploiement o2switch + contenu réel | [`phase-10.md`](./phase-10.md) | 8, 9 |

## External sources

| URL | What it settled |
| --- | --- |
| https://getbootstrap.com/docs/6.0/getting-started/install/ | Bootstrap 6.0.0-alpha.1 : options d'installation (dist CSS/JS, importmap), composants et formulaires v6, color modes |
| https://faq.o2switch.fr/guides/php/heberger-application-symfony/ | Docroot vers public/, MariaDB 10.6 (à vérifier), SSH whitelist, cron Messenger avec time-limit < intervalle, MAILER_DSN URL-encodé, supervisord non installable |
| https://sulu.io | Inspiration split view : formulaires à gauche, preview live à droite |
| https://symfony.com/doc/current/security.html | Firewall, form login, rôles, remember me |
| https://github.com/scheb/2fa | TOTP 2FA, enrollment, codes de secours |
| https://symfony.com/doc/current/ux-live-components.html | Formulaires réactifs du backoffice sans framework JS |
| https://commonmark.thephpleague.com/ | Rendu markdown serveur, html_input escaped |
| https://symfony.com/doc/current/messenger.html | Bus, transport Doctrine async, failure transport, time-limit |
| https://symfony.com/doc/current/cache.html | Cache applicatif, tags d'invalidation |
| https://developers.google.com/search/docs/specialty/international/localized-versions | hreflang et alternates par langue |
| https://symfony.com/doc/current/service_container/autowiring.html | #[AsAlias] : l'implémentation porte l'alias d'autowiring du port, sans YAML |
| https://symfony.com/bundles/SchebTwoFactorBundle/current/configuration.html | totp.enabled par env : 2FA désactivable en dev par override |
| https://pestphp.com/docs/installation | Pest v5 : `composer remove phpunit/phpunit` puis `require pestphp/pest` ; phpunit ^13.4.5 en transitif, PHP ^8.4 |
| https://pestphp.com/docs/arch-testing | API arch : `toOnlyUse`, `not->toUse`, presets `php`/`security`, `ignoring()` |
| https://pestphp.com/docs/grouping-tests | `pest()->group()->in()` : groupes arch/unit/feature par dossier |
| https://mago.carthage.software/ | mago 1.59.0 : install épinglée (`--version`), `mago init` (preset PSR-12, intégration Symfony), `fmt --check` |
| https://github.com/symfony/skeleton/blob/8.0/composer.json | Skeleton officiel PHP ≥ 8.4 : replace = polyfill-ctype/iconv + php72→php84 ; php83/84 no-ops sur 8.4 (installés en réel aujourd'hui, lock observé), php85/86 et mbstring/intl actifs donc non remplacés |
| https://symfony.com/doc/current/setup.html | `check:security` est la commande réelle ; en CI, la doc recommande `composer audit` (advisories PHP + GitHub, sans CLI) |

## Impact

| Category | Verdict and evidence | Handled in |
| ----- | -------------------- | ------------------ |
| runtime | o2switch : PHP 8.4 sélectionnable, MariaDB (10.6 selon la doc, à vérifier au premier déploiement), cron cPanel pour Messenger, docroot vers public/ — observé via la FAQ o2switch | 10 |
| regression | faible — le refactor health (phase 1) déplace la sonde DBAL/Redis derrière un port Domain : comportement `/health` et `app:health` inchangé, vérifié en phase 1 ; retrait des polyfill-php83/84 du vendor (no-ops sur 8.4, observé dans le lock) ; contenu greenfield ressaisi via le backoffice, aucun import de la base preprod | 1 |
| architecture | Moteur à composants (code) + modèles composés (backoffice) dans les couches DDD existantes ; SiteRendererInterface en port Domain + décorateur cache Infrastructure ; événements in-process (ContentPublished, DraftSaved) | 2, 4, 6, 7, 8 |
| documentation | aidd_docs/memory/ décrit l'ancien micro-CMS multi-pages, un sous-domaine admin et « aucun cron o2switch » : refresh complet après approbation (project-brief, architecture, database, design, deployment, testing, codebase-map) ; corrections ciblées en phase 1 (commande sécurité, install mago) | 1 |

## Rules

| Rule | Why it applies |
| ---- | -------------- |
| Zéro ressource externe à l'exécution (JS, CSS, fonts, images) : tout servi localement via l'asset mapper ; seule exception tolérée un service obligatoire type captcha, aucun prévu | Décision verrouillée de l'utilisateur ; CSP script-src 'self' |
| Aucun mapping ORM dans le Domain : entités PHP pures, mapping Doctrine en YAML dans `src/Infrastructure/Doctrine/Mapping/` (jamais dans `config/`) | Clean architecture : le mapping est un détail d'infrastructure ; corrige l'ancienne note « attributs ORM directs » de la mémoire projet |
| Zéro déclaration de service dans `config/services.yaml` : câblage par attributs PHP (`#[AsAlias]` pour les ports, `#[Autoconfigure]`, `#[AsEventListener]`, `#[AsMessageHandler]`) ; le fichier reste minimal | Décision utilisateur : l'attribut vit dans la couche qui possède la dépendance |
| Bootstrap 6.0.0-alpha.1 épinglé (jamais ^), auto-hébergé via importmap, tokens CSS par-dessus les variables --bs-*, pas de Sass ; front et backoffice | Décision verrouillée : éprouver la v6 en isolant le style dans une couche de tokens |
| DDD event-driven : dépendance vers l'intérieur, aucun framework dans Domain, patterns Constant et Décorateur, injection d'interfaces uniquement | Règles du projet (architecture.md, coding-assertions.md) |
| Tests avant commit : Pest (unit Domain, arch toutes couches, fonctionnels Presentation), mago lint/fmt, phpstan niveau max | coding-assertions.md, testing.md |
| Le brouillon n'est jamais servi au public ni mis en cache ; un seul moteur Twig (public = preview) | Règle du projet |
| Migrations Doctrine ordonnées par année et mois (BY_YEAR_AND_MONTH) | Convention verrouillée |
| alt_text obligatoire sur tout média | Spec §6 accessibilité |
| Déploiement uniquement par SSH depuis la machine du propriétaire (CLI Go + hook post-receive), jamais de rsync, jamais de clone GitHub depuis o2switch | deployment.md, décision verrouillée |
| Échanges et livrables en français | Règle de session |

## Decisions

| Decision | Why |
| -------- | --- |
| Option B : composants définis en code (champs typés + FormType + partial Twig), modèles de page composés dans le backoffice | Flexibilité là où elle sert (structure), typage sûr ; évite le form-builder générique et le contrat fragile Twig-backoffice |
| Zéro EasyAdmin : backoffice custom Symfony UX (Live Components) sur Bootstrap 6 | Le cœur du produit (compositeur, formulaires dynamiques, preview) est custom de toute façon ; un seul outil cohérent |
| Markdown source de vérité (CommonMark), HTML brut échappé, jamais de HTML en base | Données stables et portables ; XSS éliminé au rendu |
| Brouillon/publication par langue (deux copies JSON par PageTranslation), publication = copie + événement ContentPublished ; pas d'historique de versions | La prod n'est jamais touchée par l'édition ; EN peut rester en brouillon pendant que FR est en ligne |
| One-page V1 : sections ancrées, nav dérivée des slots du modèle ; arbre de pages et multi-pages en V2 | Spec §2 ; KISS |
| FR par défaut + EN : préfixe /en/, slugs traduits, fallback 404 + masquage nav + hreflang | Décision verrouillée en session |
| Cache full-page page:{slug}:{locale} avec tags component:{id}, régénération paresseuse | Un seul GET Redis par requête ; invalidation par composant sans assemblage de fragments à runtime |
| Direction B : contact et titres de section en serif doré sur anthracite/noir | Choix de l'utilisateur sur mockups approuvés |
| Un seul domaine (timothee-consulting.fr) : /health public minimal + /admin/health détaillé derrière auth ; staging sur preprod.timothee-consulting.fr | KISS ; l'admin vit sur /admin ; monitoring préservé sans fuite d'information |
| Contact asynchrone : Messenger transport Doctrine, cron cPanel messenger:consume chaque minute (pattern doc o2switch) | Spec §5 ; o2switch n'a pas supervisord, le cron le remplace |
| MRD de la preprod (tables typées dtype, snippets) non répliqué : le moteur le supplante, contenu ressaisi via backoffice | Décision de session ; la ressaisie éprouve le moteur |
| TOTP désactivée en dev (`config/packages/dev/scheb_2fa.yaml`, `totp.enabled: false`), active en test, staging et prod | Ergonomie de dev ; les tests fonctionnels vérifient la vraie 2FA en env test |
| Matrice d'arch pragmatique : Domain = `App\Domain` seul ; Application = ni Doctrine, ni Infrastructure, ni Presentation (Symfony toléré pour le câblage par attributs) ; Presentation = jamais Infrastructure ; Infrastructure = ni Application, ni Presentation | Le câblage par attributs (#[AsEventListener], #[AsMessageHandler]) met des imports Symfony légitimes en Application ; la matrice stricte du draft aurait été rouge dès la phase 6 — actée en session |
| « Règles PSR » = PHPStan level max + strict-rules + deprecation-rules (src/) ; style PSR-12 via preset formatter mago (src/ + tests/) | Aucun package PHPStan « PSR » n'existe : la rigueur de typage est PHPStan, le style est mago |
| strict_types + classes final verrouillés par tests d'arch | Verrou posé sur un codebase de 4 fichiers ; l'imposer plus tard = churn sur tout le codebase |
| Health refactoré en phase 1 : port `HealthProbeInterface` (Domain), sonde DBAL+Redis (Infrastructure), `HealthChecker` (Application) n'injecte que le port | Le code existant violait la matrice et coding-assertions (DBAL concret injecté en Application) ; l'arch naît sans exception |
| Tests unitaires limités à la couche Domain (`tests/Unit/`, miroir de `src/Domain`) | Décision utilisateur ; testing.md |
