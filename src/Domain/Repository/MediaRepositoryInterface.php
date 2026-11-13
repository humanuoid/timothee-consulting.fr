<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Media;

interface MediaRepositoryInterface
{
    public function save(Media $media): void;

    public function find(int $id): ?Media;
}
