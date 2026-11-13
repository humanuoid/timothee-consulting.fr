<?php

declare(strict_types=1);

namespace App\Domain\I18n;

final class Locales
{
    public const FR = 'fr';
    public const EN = 'en';

    /** @var list<string> */
    public const ALL = [self::FR, self::EN];

    private function __construct()
    {
    }
}
