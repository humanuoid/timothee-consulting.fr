<?php

declare(strict_types=1);

namespace App\Presentation\Command;

use App\Application\Health\HealthChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:health', description: 'Ping MariaDB and Redis (optional) and print a JSON report.')]
final class HealthCommand extends Command
{
    public function __construct(
        private readonly HealthChecker $healthChecker,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->healthChecker->check();
        $output->writeln((string) json_encode($report, \JSON_PRETTY_PRINT));

        return $this->healthChecker->isHealthy() ? Command::SUCCESS : Command::FAILURE;
    }
}
