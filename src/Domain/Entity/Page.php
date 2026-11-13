<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final class Page
{
    public private(set) ?int $id = null;

    /**
     * Inverse side of PageContent::page. Kept as an iterable on purpose:
     * the Domain never imports Doctrine collections, hydration swaps in
     * Doctrine's own implementation.
     *
     * @var iterable<array-key, PageContent>
     */
    public private(set) iterable $contents = [];

    public private(set) \DateTimeImmutable $createdAt;

    public private(set) \DateTimeImmutable $updatedAt;

    private function __construct(
        public private(set) PageModel $model,
        public private(set) User $createdBy,
        public private(set) ?User $updatedBy = null
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public static function create(PageModel $model, User $createdBy): self
    {
        return new self($model, $createdBy);
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
