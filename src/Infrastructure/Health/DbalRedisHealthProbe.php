<?php

declare(strict_types=1);

namespace App\Infrastructure\Health;

use App\Domain\Health\HealthProbeInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsAlias(HealthProbeInterface::class)]
final class DbalRedisHealthProbe implements HealthProbeInterface
{
    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%env(REDIS_URL)%')]
        private readonly string $redisUrl
    ) {
    }

    public function probe(): array
    {
        return [
            'bdd' => $this->probeDatabase(),
            'redis' => $this->probeRedis()
        ];
    }

    private function probeDatabase(): string
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
    private function probeRedis(): string
    {
        $parts = parse_url($this->redisUrl);
        $host = \is_array($parts) ? (string) ( $parts['host'] ?? 'localhost' ) : 'localhost';
        $port = \is_array($parts) ? (int) ( $parts['port'] ?? 6379 ) : 6379;

        // The error suppression is deliberate: a refused connection is the
        // probe's signal, not a debugging failure to surface.
        // @mago-expect lint:no-error-control-operator
        $socket = @fsockopen($host, $port, $errno, $errstr, timeout: 1);
        if (!\is_resource($socket)) {
            return 'down';
        }

        fwrite($socket, data: "PING\r\n");
        $response = (string) fgets($socket);
        fclose($socket);

        return str_contains($response, 'PONG') ? 'ok' : 'down';
    }
}
