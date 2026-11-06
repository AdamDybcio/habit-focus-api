<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Auth\Application\AccessTokenIssuer;
use App\User\Domain\User;

final class FakeAccessTokenIssuer implements AccessTokenIssuer
{
    public function issue(User $user): string
    {
        return 'access-for-'.$user->id()->toRfc4122();
    }

    public function ttlSeconds(): int
    {
        return 900;
    }
}
