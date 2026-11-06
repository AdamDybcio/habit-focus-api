<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Doctrine;

use App\Auth\Domain\RefreshToken;
use App\Auth\Domain\RefreshTokenRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineRefreshTokenRepository implements RefreshTokenRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(RefreshToken $token): void
    {
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }

    public function findByHash(string $hash): ?RefreshToken
    {
        return $this->entityManager->getRepository(RefreshToken::class)->findOneBy(['tokenHash' => $hash]);
    }

    public function findByHashForUpdate(string $hash): ?RefreshToken
    {
        $token = $this->entityManager->createQueryBuilder()
            ->select('t')
            ->from(RefreshToken::class, 't')
            ->where('t.tokenHash = :hash')
            ->setParameter('hash', $hash)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        return $token instanceof RefreshToken ? $token : null;
    }

    public function revokeFamily(Uuid $familyId, \DateTimeImmutable $now): void
    {
        $this->entityManager->createQueryBuilder()
            ->update(RefreshToken::class, 't')
            ->set('t.revokedAt', ':now')
            ->where('t.familyId = :family')
            ->andWhere('t.revokedAt IS NULL')
            ->setParameter('now', $now, 'datetime_immutable')
            ->setParameter('family', $familyId, UuidType::NAME)
            ->getQuery()
            ->execute();

        // Bulk updates bypass the identity map, drop stale copies.
        $this->entityManager->clear();
    }

    public function deleteExpiredBefore(\DateTimeImmutable $moment): int
    {
        $deleted = $this->entityManager->createQueryBuilder()
            ->delete(RefreshToken::class, 't')
            ->where('t.expiresAt < :moment')
            ->setParameter('moment', $moment, 'datetime_immutable')
            ->getQuery()
            ->execute();

        return (int) $deleted;
    }
}
