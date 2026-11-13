<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Media;
use App\Domain\Repository\MediaRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MediaRepositoryInterface::class)]
final class MediaRepository implements MediaRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function save(Media $media): void
    {
        $this->entityManager->persist($media);
        $this->entityManager->flush();
    }

    public function find(int $id): ?Media
    {
        $media = $this->entityManager->find(Media::class, $id);

        return $media instanceof Media ? $media : null;
    }
}
