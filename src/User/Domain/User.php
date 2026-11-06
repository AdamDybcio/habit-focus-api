<?php

declare(strict_types=1);

namespace App\User\Domain;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * An account that can log in. The identifier used in tokens is the UUID, never
 * the email, so changing an email later does not invalidate sessions.
 */
#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $passwordHash = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    private function __construct(Uuid $id, string $email, \DateTimeImmutable $createdAt)
    {
        $this->id = $id;
        $this->email = $email;
        $this->createdAt = $createdAt;
    }

    /**
     * Creates a user without a password. Call {@see self::changePasswordHash()}
     * before saving it.
     */
    public static function register(Uuid $id, string $email, \DateTimeImmutable $createdAt): self
    {
        return new self($id, self::normalizeEmail($email), $createdAt);
    }

    /**
     * Emails are compared case-insensitively, so they are stored lowercase.
     */
    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function changePasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUserIdentifier(): string
    {
        return $this->id->toRfc4122();
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase: the plain password is never stored on the entity.
    }
}
