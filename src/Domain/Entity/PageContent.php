<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\I18n\Locales;

final class PageContent
{
    public private(set) ?int $id = null;

    /**
     * Working copy by slot, filled from the backoffice editor. The payload
     * structure is defined by the component library (phases 4/6).
     *
     * @var array<string, mixed>
     */
    public private(set) array $draft = [];

    /**
     * @var array<string, mixed>|null
     */
    public private(set) ?array $published = null;

    public private(set) ?\DateTimeImmutable $publishedAt = null;

    public private(set) \DateTimeImmutable $createdAt;

    public private(set) \DateTimeImmutable $updatedAt;

    private function __construct(
        public private(set) Page $page,
        public private(set) string $locale,
        public private(set) string $slug,
        public private(set) string $title,
        public private(set) string $metaTitle,
        public private(set) string $metaDescription
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public static function create(
        Page $page,
        string $locale,
        string $slug,
        string $title,
        string $metaTitle,
        string $metaDescription
    ): self {
        if (!\in_array($locale, Locales::ALL, strict: true)) {
            throw new \InvalidArgumentException(\sprintf(
                'Unknown locale "%s", expected one of: %s.',
                $locale,
                \implode(', ', Locales::ALL)
            ));
        }

        return new self($page, $locale, $slug, $title, $metaTitle, $metaDescription);
    }

    public function isPublished(): bool
    {
        return $this->published !== null;
    }

    /**
     * The publication is a copy, never a move: the draft stays editable
     * while the published version serves the public site.
     */
    public function publish(): void
    {
        $this->published = $this->draft;
        $this->publishedAt = new \DateTimeImmutable();
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
