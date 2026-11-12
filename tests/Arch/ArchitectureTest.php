<?php

declare(strict_types=1);

/*
 * One rule per architecture rule of architecture.md, all layers.
 * Matrix locked in plan decisions: Domain uses nothing external;
 * Application may use Symfony for wiring but nothing else beyond Domain;
 * Presentation never touches Infrastructure; ports are implemented by
 * Infrastructure only.
 *
 * toUse/toOnlyUse match multi-segment namespace prefixes only: a bare
 * top segment ('Doctrine', 'Symfony') matches nothing — verified with
 * probes during phase 1.
 */

arch('domain ne dépend de rien')
    ->expect('App\Domain')
    ->toOnlyUse('App\Domain');

arch('application: domain et symfony de câblage seulement')
    ->expect('App\Application')
    ->toOnlyUse(['App\Application', 'App\Domain', 'Symfony\Component', 'Symfony\Contracts']);

arch('presentation ne touche jamais l\'infrastructure')
    ->expect('App\Presentation')
    ->not->toUse('App\Infrastructure');

arch('infrastructure: ni application, ni presentation')
    ->expect('App\Infrastructure')
    ->not->toUse(['App\Application', 'App\Presentation']);

arch('strict types partout')
    ->expect('App')
    ->toUseStrictTypes();

arch('classes final')
    ->expect('App')
    ->classes()
    ->toBeFinal();

arch()->preset()->php();

arch()->preset()->security();
