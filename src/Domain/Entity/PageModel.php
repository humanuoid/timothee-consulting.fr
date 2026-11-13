<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final class PageModel
{
    public private(set) ?int $id = null;

    public private(set) \DateTimeImmutable $createdAt;

    public private(set) \DateTimeImmutable $updatedAt;

    /**
     * @param list<string> $slots ordered component keys, the public
     *                            navigation derives from them
     */
    private function __construct(
        public private(set) string $name,
        // property is not named "key": KEY is a reserved SQL word, the
        // column is model_key
        public private(set) string $modelKey,
        /** @var list<string> */
        public private(set) array $slots
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    /**
     * @param list<string> $slots
     */
    public static function create(string $name, string $modelKey, array $slots): self
    {
        return new self($name, $modelKey, $slots);
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
