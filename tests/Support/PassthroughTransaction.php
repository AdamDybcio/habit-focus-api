<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Application\Transaction;

/**
 * Runs the operation directly. In-memory fakes have nothing to roll back.
 */
final class PassthroughTransaction implements Transaction
{
    public function wrap(callable $operation): mixed
    {
        return $operation();
    }
}
