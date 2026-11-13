<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final class User
{
    public private(set) ?int $id = null;

    public private(set) bool $mustChangePassword;

    public private(set) \DateTimeImmutable $createdAt;

    public private(set) \DateTimeImmutable $updatedAt;

    /**
     * @param list<string> $roles
     * @param list<string>|null $recoveryCodes
     */
    private function __construct(
        public private(set) string $email,
        public private(set) string $passwordHash,
        public private(set) array $roles,
        public private(set) string $fullName,
        public private(set) string $locale,
        #[\SensitiveParameter]
        public private(set) ?string $totpSecret = null,
        public private(set) ?array $recoveryCodes = null
    ) {
        // Credentials are handed over once, then rotated by the account
        // owner at first login.
        $this->mustChangePassword = true;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    /**
     * @param list<string> $roles
     */
    public static function create(
        string $email,
        string $passwordHash,
        array $roles,
        string $fullName,
        string $locale
    ): self {
        return new self($email, $passwordHash, $roles, $fullName, $locale);
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
