<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final class Media
{
    public private(set) ?int $id = null;

    public private(set) \DateTimeImmutable $createdAt;

    private function __construct(
        public private(set) string $fileName,
        public private(set) string $altText,
        public private(set) string $path,
        public private(set) string $mimeType,
        public private(set) int $size
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(
        string $fileName,
        string $altText,
        string $path,
        string $mimeType,
        int $size
    ): self {
        // alt_text is mandatory: without it an image is unusable to a
        // screen reader (spec §6, accessibility).
        if (trim($altText) === '') {
            throw new \InvalidArgumentException('The alt text of a media cannot be empty.');
        }

        return new self($fileName, $altText, $path, $mimeType, $size);
    }
}
