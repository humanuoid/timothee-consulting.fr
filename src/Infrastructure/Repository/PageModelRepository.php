<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\PageModel;
use App\Domain\Repository\PageModelRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PageModelRepositoryInterface::class)]
final class PageModelRepository implements PageModelRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function save(PageModel $model): void
    {
        $model->touch();
        $this->entityManager->persist($model);
        $this->entityManager->flush();
    }

    public function findByKey(string $key): ?PageModel
    {
        $model = $this->entityManager->getRepository(PageModel::class)->findOneBy(['modelKey' => $key]);

        return $model instanceof PageModel ? $model : null;
    }
}
