<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\PageModel;

interface PageModelRepositoryInterface
{
    public function save(PageModel $model): void;

    public function findByKey(string $key): ?PageModel;
}
