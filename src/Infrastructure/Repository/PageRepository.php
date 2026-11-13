<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Page;
use App\Domain\Repository\PageRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PageRepositoryInterface::class)]
final class PageRepository implements PageRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function save(Page $page): void
    {
        $page->touch();
        $this->entityManager->persist($page);
        $this->entityManager->flush();
    }

    public function find(int $id): ?Page
    {
        $page = $this->entityManager->find(Page::class, $id);

        return $page instanceof Page ? $page : null;
    }
}
