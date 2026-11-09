<?php

namespace App\Application\Health;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Probes external dependencies. Redis is optional: when it is down the site
 * falls back to the database, so only the database ping can fail the health.
 */
final class HealthChecker
{
    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%env(REDIS_URL)%')]
        private readonly string $redisUrl,
    ) {
    }

    /** @return array{php: string, bdd: string, redis: string, time: string} */
    public function check(): array
    {
        return [
            'php' => \PHP_VERSION,
            'bdd' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'time' => date(\DATE_ATOM),
        ];
    }

    public function isHealthy(): bool
    {
        return 'ok' === $this->check()['bdd'];
    }

    private function checkDatabase(): string
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return 'ok';
        } catch (\Throwable) {
            return 'down';
        }
    }

    /**
     * ext-redis is not required on purpose: a raw RESP PING over TCP keeps the
     * health check dependency-free.
     */
    private function checkRedis(): string
    {
        $parts = parse_url($this->redisUrl);
        $host = $parts['host'] ?? 'localhost';
        $port = (int) ($parts['port'] ?? 6379);

        $socket = @fsockopen($host, $port, $errno, $errstr, 1);
        if (!\is_resource($socket)) {
            return 'down';
        }

        fwrite($socket, "PING\r\n");
        $response = (string) fgets($socket);
        fclose($socket);

        return str_contains($response, 'PONG') ? 'ok' : 'down';
    }
}
