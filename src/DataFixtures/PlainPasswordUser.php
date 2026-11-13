<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Minimal adapter so the fixtures can hash passwords without making the
 * Domain User implement a framework interface (the SecurityUser adapter
 * arrives in phase 3).
 */
final class PlainPasswordUser implements PasswordAuthenticatedUserInterface
{
    public function __construct(
        #[\SensitiveParameter]
        private readonly string $plainPassword
    ) {
    }

    public function getPassword(): string
    {
        return $this->plainPassword;
    }
}
