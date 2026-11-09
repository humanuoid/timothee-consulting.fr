# Architecture

## Stack

- PHP + Symfony LTS, installé via Symfony CLI (pas de `composer create-project`)
- MariaDB via Doctrine ORM (entités + migrations), Redis comme cache/read model optionnel
- Twig : un seul moteur de rendu, partagé entre site public, preview admin et dashboard
- Symfony UX (Turbo, Stimulus, Live Components) ; assets JS en TypeScript compilés par SWC via `sensiolabs/typescript-bundle` (zéro Node), servis par l'asset mapper ; contrôleurs Stimulus écrits en TS
- Hébergement o2switch (mutualisé) ; déploiement par SSH, uniquement depuis la machine du propriétaire

## Structure DDD et règle de dépendance

```txt
Presentation ──► Application ──► Domain ◄── Infrastructure
                      └────────────► (implémente les ports du Domain)
```

- `src/Domain` ne dépend de rien (ni Symfony, ni Doctrine) : agrégats, value objects, événements, ports (interfaces de repositories).
- `src/Application` dépend de Domain : services applicatifs, handlers d'événements, orchestration.
- `src/Infrastructure` implémente les ports de Domain (repositories Doctrine, cache Redis, mailer).
- `src/Presentation` (contrôleurs, Live Components, formulaires, commandes console) ne fait que du HTTP/CLI → Application.
- **Règle absolue : la dépendance va toujours vers l'intérieur. Jamais l'inverse.**

## Où va quoi

| Type de code | Destination |
| --- | --- |
| Entité/agrégat, value object, événement, port (interface) | `src/Domain/...` |
| Service applicatif, handler d'événement, DTO | `src/Application/...` |
| Repository Doctrine, adapter Redis/mailer | `src/Infrastructure/...` |
| Contrôleur, Live Component, Form Type, Command console | `src/Presentation/...` |
| Templates Twig du site public | `templates/site/` |
| Templates Twig du dashboard | `templates/admin/` |
| Assets TS + contrôleurs Stimulus | `assets/` → SWC (typescript-bundle) → servi par l'asset mapper |

Note pragmatique : les entités Domain portent les attributs ORM directement (pas de mapping XML séparé — KISS).

## SOLID appliqués

- **S** — un service = une responsabilité : `DraftService` (brouillon), `PublishService` (publication), `VersioningService` (snapshots). Jamais de `ContentManager` fourre-tout.
- **O** — un nouveau type de bloc s'ajoute sans modifier le code existant : les formulaires et partials Twig sont résolus par convention de nom à partir du type (`hero` → `HeroBlockType` + `_partials/hero.html.twig`).
- **L** — `RedisCachedSiteRenderer` et `CacheAsideSiteRenderer` sont interchangeables derrière `SiteRendererInterface` ; le contrôleur ne change pas.
- **I** — des ports fins : `PageRepositoryInterface::findPublishedBySlug(string $slug)`, pas une interface de 15 méthodes.
- **D** — on injecte toujours l'interface (le port), jamais l'implémentation concrète Doctrine/Redis.

## KISS / DRY

- La solution la plus simple qui marche. Pas de CQRS, pas de bus maison : le messenger natif de Symfony suffit.
- Un seul moteur de rendu Twig (public = preview = admin) : jamais de rendu dupliqué.
- Aucune chaîne magique en dur : noms d'événements, tags de cache, slugs métier passent par des constantes (voir Constant).

## Pattern Constant (imposé)

```php
// src/Domain/Cache/CacheTags.php
final class CacheTags
{
    public static function forPage(string $slug): string
    {
        return 'page:'.$slug;
    }
}

// src/Domain/Event/EventNames.php
final class EventNames
{
    public const CONTENT_PUBLISHED = 'content.published';
    public const DRAFT_SAVED = 'draft.saved';
}
```

Règle : tout nom d'événement, tag de cache ou clé métier passe par une constante dans `src/Domain`. Recherche globale fiable, refactoring sûr, zéro faute de frappe.

## Pattern Décorateur (imposé)

```php
// src/Domain/Service/SiteRendererInterface.php — le port
interface SiteRendererInterface
{
    public function render(Page $page, bool $useDraft): string;
}

// src/Infrastructure/Cache/CacheAsideSiteRenderer.php — lit la BDD
final class CacheAsideSiteRenderer implements SiteRendererInterface { /* ... */ }

// src/Infrastructure/Cache/RedisCachedSiteRenderer.php — le décorateur
final class RedisCachedSiteRenderer implements SiteRendererInterface
{
    public function __construct(
        private readonly SiteRendererInterface $inner, // le décoré
        private readonly CacheInterface $redisCache,
    ) {}

    public function render(Page $page, bool $useDraft): string
    {
        if ($useDraft) {
            return $this->inner->render($page, true); // le brouillon n'est JAMAIS mis en cache
        }
        try {
            return $this->redisCache->get(
                CacheTags::forPage($page->getSlug()),
                fn () => $this->inner->render($page, false),
            );
        } catch (\Throwable) {
            return $this->inner->render($page, false); // Redis down → fallback BDD, le site répond toujours
        }
    }
}
```

Règles : le décorateur implémente la même interface que le décoré ; le fallback garantit que le site fonctionne sans Redis ; la preview (brouillon) contourne toujours le cache.

## Event-driven

- Un événement = un fait métier, émis par le Domain : `ContentPublished`, `DraftSaved`.
- Un listener = un seul effet de bord : `InvalidateCacheOnPublish` (Application), etc.
- La publication : `PublishService` fait le flip + le snapshot + `dispatch(new ContentPublished(...))`. Le contrôleur ne sait rien du cache.
- Événements nommés via `EventNames` (constantes), listeners câblés par attribut ou `config/services.yaml`.

## Gotchas

- o2switch ne peut pas joindre github.com (DNS non autorisés) : le déploiement passe par SSH depuis la machine locale (voir `deployment.md`).
- o2switch fournit MariaDB et Redis en natif ; aucun cron o2switch (Redis géré par l'hébergeur, sa chute n'est pas dramatique).
- Preview = même moteur Twig que le public : ne jamais dupliquer un moteur de rendu.

## Références externes

- https://ngandu.dev/fr/blog/symfony-clean-architecture-ddd (clean architecture + DDD en Symfony)
- OpenClassrooms, parcours design patterns (Constant, Décorateur)
