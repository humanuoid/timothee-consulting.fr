<?php

declare(strict_types=1);

namespace App\Domain\Health;

interface HealthProbeInterface
{
    /**
     * @return array{bdd: string, redis: string} 'ok' or 'down' per dependency
     */
    public function probe(): array;
}
