<?php

declare(strict_types=1);

namespace App\User\Domain;

use App\User\Domain\Exception\EmailAlreadyRegistered;
use Symfony\Component\Uid\Uuid;

interface UserRepository
{
    public function findById(Uuid $id): ?User;

    /**
     * @param string $email an already normalized email, see {@see User::normalizeEmail()}
     */
    public function findByEmail(string $email): ?User;

    /**
     * @throws EmailAlreadyRegistered when another user took the email in the meantime
     */
    public function save(User $user): void;
}
