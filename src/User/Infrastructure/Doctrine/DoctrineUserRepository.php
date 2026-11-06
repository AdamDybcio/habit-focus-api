<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Doctrine;

use App\User\Domain\Exception\EmailAlreadyRegistered;
use App\User\Domain\User;
use App\User\Domain\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineUserRepository implements UserRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findById(Uuid $id): ?User
    {
        return $this->entityManager->find(User::class, $id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // The unique index is the source of truth when two requests race.
            throw new EmailAlreadyRegistered();
        }
    }
}
