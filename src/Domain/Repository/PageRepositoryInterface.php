<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Page;

interface PageRepositoryInterface
{
    public function save(Page $page): void;

    public function find(int $id): ?Page;
}
