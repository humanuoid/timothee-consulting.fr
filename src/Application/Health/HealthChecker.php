<?php

declare(strict_types=1);

namespace App\Application\Health;

use App\Domain\Health\HealthProbeInterface;

/**
 * Assembles the health report from the infrastructure probe. Redis is
 * optional: when it is down the site falls back to the database, so only
 * the database status can fail the health.
 */
final class HealthChecker
{
    public function __construct(
        private readonly HealthProbeInterface $healthProbe
    ) {
    }

    /**
     * @return array{php: string, bdd: string, redis: string, time: string}
     */
    public function check(): array
    {
        $probe = $this->healthProbe->probe();

        return [
            'php' => \PHP_VERSION,
            'bdd' => $probe['bdd'],
            'redis' => $probe['redis'],
            'time' => date(\DATE_ATOM)
        ];
    }

    public function isHealthy(): bool
    {
        return 'ok' === $this->check()['bdd'];
    }
}
