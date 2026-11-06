<?php

declare(strict_types=1);

namespace App\Shared\Application;

/**
 * Runs an operation atomically: everything inside commits together, or rolls
 * back when the operation throws.
 */
interface Transaction
{
    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function wrap(callable $operation): mixed;
}
